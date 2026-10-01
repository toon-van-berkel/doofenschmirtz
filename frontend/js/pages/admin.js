import { adminApi, verificationsApi } from '../api/index.js';
import { assetUrl } from '../api/client.js';
import { setButtonLoading, showApiError, showFeedback } from '../components/feedback.js';

function renderImages(images, alt) {
    return (images || []).map((image) =>
        `<img src="${assetUrl(image.image_url)}" alt="${alt}" loading="lazy">`
    ).join('');
}

function renderTasks(tasks) {
    const root = document.querySelector('#admin-list');
    root.innerHTML = '';
    if (!tasks.length) { root.textContent = 'No pending tasks.'; return; }
    tasks.forEach((task) => {
        const card = document.createElement('article');
        card.className = 'task-card';
        card.innerHTML = `<h2>${task.title}</h2><p>Creator: ${task.creator_username}</p>
            <p>${task.description || ''}</p><p>${task.completion_criteria || ''}</p>
            <p>${task.location_description || ''}</p><p>Status: ${task.status}</p>
            <div>${renderImages(task.images, 'Task image')}</div>`;
        const reward = document.createElement('input');
        reward.type = 'number'; reward.min = '0'; reward.value = '20';
        const approve = document.createElement('button'); approve.textContent = 'Approve';
        approve.onclick = async () => {
            setButtonLoading(approve, true, 'Approving...');
            reject.disabled = true;
            try {
                await adminApi.approve({ task_id: task.id, points: Number(reward.value) });
                showFeedback({ type: 'success', title: 'Task approved', message: 'The task is now available to other users.' });
                card.remove();
            } catch (error) {
                setButtonLoading(approve, false);
                reject.disabled = false;
                showApiError(error, { title: 'Task could not be approved' });
            }
        };
        const reject = document.createElement('button'); reject.textContent = 'Reject';
        reject.onclick = async () => {
            setButtonLoading(reject, true, 'Rejecting...');
            approve.disabled = true;
            try {
                await adminApi.reject({ task_id: task.id });
                showFeedback({ type: 'success', title: 'Task rejected', message: 'The task will not be published.' });
                card.remove();
            } catch (error) {
                setButtonLoading(reject, false);
                approve.disabled = false;
                showApiError(error, { title: 'Task could not be rejected' });
            }
        };
        card.append(reward, approve, reject); root.append(card);
    });
}

function renderAppeals(appeals) {
    const root = document.querySelector('#appeal-list');
    root.innerHTML = '';
    if (!appeals.length) { root.textContent = 'No pending third-party checks.'; return; }
    appeals.forEach((appeal) => {
        const card = document.createElement('article');
        card.className = 'task-card';
        card.innerHTML = `<h2>${appeal.title}</h2><p>Location: ${appeal.location_description || 'Not specified'}</p>
            <p>Reason: ${appeal.reason}</p><p>${appeal.evidence_description || ''}</p>
            <div>${renderImages(appeal.images, 'Evidence')}</div>`;
        ['approved', 'rejected'].forEach((decision) => {
            const button = document.createElement('button'); button.textContent = decision === 'approved' ? 'Approve' : 'Reject';
            button.onclick = async () => {
                const siblingButtons = [...card.querySelectorAll('button')];
                siblingButtons.forEach((actionButton) => setButtonLoading(actionButton, true, 'Working...'));
                try {
                    await verificationsApi.reviewAppeal({ appeal_id: appeal.appeal_id, decision });
                    showFeedback({
                        type: 'success',
                        title: decision === 'approved' ? 'Third-party check approved' : 'Third-party check rejected',
                        message: decision === 'approved'
                            ? 'The creator rejection was overridden and the task has been completed.'
                            : 'The original rejection has been confirmed.'
                    });
                    card.remove();
                } catch (error) {
                    siblingButtons.forEach((actionButton) => setButtonLoading(actionButton, false));
                    showApiError(error, { title: 'Third-party check could not be saved' });
                }
            };
            card.append(button);
        });
        root.append(card);
    });
}

adminApi.tasks().then(({ tasks }) => renderTasks(tasks)).catch((error) => { document.querySelector('#admin-list').textContent = error.message; });
verificationsApi.appeals().then(({ appeals }) => renderAppeals(appeals)).catch((error) => { document.querySelector('#appeal-list').textContent = error.message; });
