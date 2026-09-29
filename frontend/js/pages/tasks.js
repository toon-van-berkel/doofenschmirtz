import { tasksApi } from '../api/index.js';

// TODO [TASK LIST]: Use tasksApi.list() for GET /api/tasks and render loading, empty, error, and open-task states.
// Each task should show title, description/criteria summary, approved reward, and location,
// with an action that loads tasksApi.view(taskId). Authentication must be active for the page.
// TODO [TASK CREATION]: Add an authenticated form using tasksApi.create() / POST /api/tasks/create.
// The server owns pending status and reward approval; the user must not choose points.

const form = document.querySelector('#task-form');
const formMessage = document.querySelector('#form-message');
const taskMessage = document.querySelector('#task-message');
const taskList = document.querySelector('#task-list');

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
        button.addEventListener('click', () => showTask(task.id, card));

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

    const data = new FormData(form);
    const task = Object.fromEntries(data.entries());

    task.latitude = task.latitude === '' ? null : Number(task.latitude);
    task.longitude = task.longitude === '' ? null : Number(task.longitude);

    try {
        await tasksApi.create(task);
        form.reset();
        formMessage.textContent = 'Task created and waiting for approval.';
        await loadTasks();
    } catch (error) {
        formMessage.textContent = error.message;
    }
});

loadTasks();
