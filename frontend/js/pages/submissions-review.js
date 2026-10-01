import { verificationsApi } from '../api/index.js';
import { assetUrl } from '../api/client.js';
import { setButtonLoading, showApiError, showFeedback } from '../components/feedback.js';

function render(root, rows, actions) {
    root.innerHTML = '';
    if (!rows.length) { root.textContent = 'Nothing pending.'; return; }
    rows.forEach((row) => {
        const item = document.createElement('article');
        item.className = 'task-card';
        const images = (row.images || []).map((image) =>
            `<img src="${assetUrl(image.image_url)}" alt="Evidence" loading="lazy">`
        ).join('');
        item.innerHTML = `<h3>${row.title ?? 'Submission'}</h3>
            <p>Location: ${row.location_description ?? 'Not specified'}</p>
            <p>${row.evidence_description ?? ''}</p><div>${images}</div>`;
        const buttons = [];
        actions.forEach(([label, callback]) => {
            const button = document.createElement('button');
            button.textContent = label;
            button.onclick = async () => {
                buttons.forEach((actionButton) => setButtonLoading(actionButton, true, 'Working...'));
                try {
                    await callback(row);
                    showFeedback({
                        type: 'success',
                        title: label.startsWith('Yes') || label.startsWith('No')
                            ? 'Validation submitted'
                            : label === 'Approve' ? 'Submission approved' : 'Submission rejected',
                        message: label.startsWith('Yes') || label.startsWith('No')
                            ? 'Your community validation has been recorded.'
                            : label === 'Approve'
                                ? 'The task has been completed and the completion reward was awarded.'
                                : 'The submitter can accept the rejection or request a third-party check.'
                    });
                    item.remove();
                } catch (error) {
                    buttons.forEach((actionButton) => setButtonLoading(actionButton, false));
                    showApiError(error, { title: 'Review could not be saved' });
                }
            };
            item.append(button);
            buttons.push(button);
        });
        root.append(item);
    });
}

const creatorQueue = document.querySelector('#creator-queue');
const communityQueue = document.querySelector('#community-queue');

verificationsApi.creatorQueue().then(({ submissions }) => render(creatorQueue, submissions, [
    ['Approve', (row) => verificationsApi.creatorReview({ submission_id: row.submission_id, decision: 'approved' })],
    ['Reject', (row) => verificationsApi.creatorReview({ submission_id: row.submission_id, decision: 'rejected' })],
])).catch((error) => { creatorQueue.textContent = error.message; });

verificationsApi.communityQueue().then(({ submissions }) => render(communityQueue, submissions, [
    ['Yes, task completed', (row) => verificationsApi.communityReview({ submission_id: row.submission_id, decision: 'approved' })],
    ['No, task not completed', (row) => verificationsApi.communityReview({ submission_id: row.submission_id, decision: 'rejected' })],
])).catch((error) => { communityQueue.textContent = error.message; });
