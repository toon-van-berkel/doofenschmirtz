import { tasksApi, submissionsApi } from '../api/index.js';
import { assetUrl } from '../api/client.js';
import { setButtonLoading, showApiError, showFeedback } from '../components/feedback.js';

const id = new URLSearchParams(window.location.search).get('task_id');
const message = document.querySelector('#detail-message');
const detail = document.querySelector('#task-detail');
const form = document.querySelector('#submission-form');
const submitButton = form.querySelector('button[type="submit"]');

function text(label, value) {
    const p = document.createElement('p');
    p.textContent = `${label}: ${value ?? ''}`;
    detail.append(p);
}

tasksApi.view(id)
    .then(({ task }) => {
        detail.innerHTML = '';
        const heading = document.createElement('h1'); heading.textContent = task.title; detail.append(heading);
        text('Description', task.description); text('Completion criteria', task.completion_criteria);
        text('Location', task.location_description); text('Creator', task.creator_username); text('Points', task.points);
        (task.images || []).forEach((image) => {
            const img = document.createElement('img');
            img.src = assetUrl(image.image_url);
            img.alt = image.original_filename || 'Task image';
            img.loading = 'lazy';
            detail.append(img);
        });
        form.hidden = false;
        message.textContent = '';
    })
    .catch((error) => { message.textContent = error.message; });

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setButtonLoading(submitButton, true, 'Submitting...');
    try {
        const data = new FormData(form);
        data.set('task_id', id);
        await submissionsApi.create(data);
        message.textContent = 'Submission sent.';
        form.reset();
        showFeedback({
            type: 'success',
            title: 'Submission sent',
            message: 'Your evidence has been sent to the task creator for review.'
        });
    }
    catch (error) { message.textContent = error.message; showApiError(error, { title: 'Submission could not be sent', fallbackMessage: 'Please check your evidence and try again.' }); }
    finally { setButtonLoading(submitButton, false); }
});
