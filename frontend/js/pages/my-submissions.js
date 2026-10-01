import { submissionsApi, verificationsApi } from '../api/index.js';
import { assetUrl } from '../api/client.js';
import { setButtonLoading, showApiError, showFeedback } from '../components/feedback.js';

const root = document.querySelector('#submission-list');

async function loadSubmissions() {
    const { submissions } = await submissionsApi.mine();
    root.innerHTML = '';

    if (!submissions.length) {
        root.textContent = 'No submissions yet.';
        return;
    }

    submissions.forEach((submission) => {
        const card = document.createElement('article');
        card.className = 'task-card';
        const images = (submission.images || []).map((image) =>
            `<img src="${assetUrl(image.image_url)}" alt="Evidence" loading="lazy">`
        ).join('');
        card.innerHTML = `<h2>${submission.title}</h2>
            <p>Status: ${submission.status}</p>
            <p>${submission.evidence_description || ''}</p><div>${images}</div>`;

        if (submission.status === 'creator_rejected') {
            const accept = document.createElement('button');
            accept.textContent = 'Accept rejection';
            accept.onclick = async () => {
                setButtonLoading(accept, true, 'Saving...');
                try {
                    await verificationsApi.acceptRejection({ submission_id: submission.id });
                    showFeedback({ type: 'success', title: 'Rejection accepted', message: 'This submission has been closed as rejected.' });
                    await loadSubmissions();
                } catch (error) {
                    showApiError(error, { title: 'Rejection could not be accepted' });
                    setButtonLoading(accept, false);
                }
            };

            const appeal = document.createElement('button');
            appeal.textContent = 'Request third-party check';
            appeal.onclick = async () => {
                const reason = prompt('Reason for third-party check');
                if (!reason) return;
                setButtonLoading(appeal, true, 'Requesting...');
                try {
                    await verificationsApi.requestAppeal({ submission_id: submission.id, reason });
                    showFeedback({ type: 'success', title: 'Third-party check requested', message: 'An admin can now review the rejected submission.' });
                    await loadSubmissions();
                } catch (error) {
                    showApiError(error, { title: 'Third-party check could not be requested' });
                    setButtonLoading(appeal, false);
                }
            };
            card.append(accept, appeal);
        }
        root.append(card);
    });
}

loadSubmissions().catch((error) => { root.textContent = error.message; });
