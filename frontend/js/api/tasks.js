import { api } from './client.js';

export const tasksApi = {
    list() {
        // TODO [TASK LIST]: Fetch open tasks from GET /tasks. The backend should return JSON task/status metadata,
        // with filtering and pagination added when the task list UI is implemented.
        return api.get('/tasks');
    },

    view(taskId) {
        // TODO [TASK VIEW]: Fetch one task from GET /tasks/view?task_id= and show only data the current user may view.
        return api.get(`/tasks/view?task_id=${encodeURIComponent(taskId)}`);
    },

    mine() {
        // TODO [MY TASKS]: Fetch the authenticated user's created tasks from GET /tasks/mine.
        return api.get('/tasks/mine');
    },

    create(payload) {
        // TODO [TASK CREATION]: Send task fields to POST /tasks/create. The server owns pending status and reward approval.
        return api.post('/tasks/create', payload);
    }
};
