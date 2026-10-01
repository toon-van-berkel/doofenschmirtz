import { tasksApi } from '../api/index.js';
import { setButtonLoading, showApiError, showFeedback } from '../components/feedback.js';

// --- Team integration: Tasks ---
// Original feature author: Liam Plokkaar
// Source branch: taskpage
// Original task implementation preserved.
// Integration by Toon van Berkel: connected to the current application structure.

// The page lists open tasks and creates pending tasks through the authenticated API.
// TODO [OPTIONAL]: Connect the existing search control and add pagination if needed.

const form = document.querySelector('#task-form');
const formMessage = document.querySelector('#form-message');
const taskMessage = document.querySelector('#task-message');
const taskList = document.querySelector('#task-list');
const locationStatus = document.querySelector('#location-status');
const submitButton = form.querySelector('button[type="submit"]');

function addText(element, label, value) {
    if (value !== null && value !== '') {
        const line = document.createElement('p');
        line.className = 'task-detail';
        line.textContent = `${label}: ${value}`;
        element.append(line);
    }
}
async function showTask(taskId, card) {
    try {
        const response = await tasksApi.view(taskId);
        const task = response.task;
        card.innerHTML = '';

        const title = document.createElement('h3');
        title.className = 'task-card__title';
        title.textContent = task.title;
        card.append(title);
        addText(card, 'Description', task.description);
        addText(card, 'Completion criteria', task.completion_criteria);
        addText(card, 'Points', task.points);
        addText(card, 'Location', task.location_description);
        addText(card, 'Created by', task.creator_username);
    } catch (error) {
        taskMessage.textContent = error.message;
    }
}

function renderTasks(tasks) {
    taskList.innerHTML = '';

    if (tasks.length === 0) {
        taskMessage.textContent = 'There are no open tasks.';
        return;
    }

    taskMessage.textContent = '';

    tasks.forEach((task) => {
        const card = document.createElement('article');
        const title = document.createElement('h3');
        const description = document.createElement('p');
        const button = document.createElement('button');

        card.className = 'task-card';
        title.className = 'task-card__title';
        description.className = 'task-card__description';
        button.className = 'task-card__button';
        title.textContent = task.title;
        description.textContent = task.description;
        button.textContent = 'View task';
        button.addEventListener('click', () => { window.location.href = `./task-detail.html?task_id=${encodeURIComponent(task.id)}`; });

        card.append(title, description);
        addText(card, 'Points', task.points);
        addText(card, 'Location', task.location_description);
        card.append(button);
        taskList.append(card);
    });
}

async function loadTasks() {
    taskMessage.textContent = 'Loading tasks...';

    try {
        const response = await tasksApi.list();
        renderTasks(response.tasks);
    } catch (error) {
        if (error.status === 401) {
            window.location.href = './login.html';
            return;
        }

        taskMessage.textContent = error.message;
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    formMessage.textContent = 'Creating task...';
    setButtonLoading(submitButton, true, 'Creating task...');

    try {
        // Use the browser's current position so the creator does not manually enter coordinates.
        const position = await new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported by this browser.'));
                return;
            }
            if (locationStatus) locationStatus.textContent = 'Getting your location...';
            navigator.geolocation.getCurrentPosition(
                resolve,
                // Do not silently submit fake or empty coordinates when permission is denied.
                () => reject(new Error('Location permission is required to create a task.'))
            );
        });
        const data = new FormData(form);
        data.set('latitude', String(position.coords.latitude));
        data.set('longitude', String(position.coords.longitude));
        await tasksApi.create(data);
        form.reset();
        if (locationStatus) locationStatus.textContent = 'Location captured.';
        formMessage.textContent = 'Task created and waiting for approval.';
        showFeedback({
            type: 'success',
            title: 'Task submitted',
            message: 'Your task has been sent for admin approval. It will appear in the open task list after it has been approved.'
        });
        await loadTasks();
    } catch (error) {
        formMessage.textContent = error.message;
        showApiError(error, {
            title: error.message?.includes('Location') ? 'Location required' : 'Task could not be created',
            fallbackMessage: error.message?.includes('Location')
                ? 'Allow location access before creating this task.'
                : 'Please check the information and try again.'
        });
    } finally {
        setButtonLoading(submitButton, false);
    }
});

loadTasks();
