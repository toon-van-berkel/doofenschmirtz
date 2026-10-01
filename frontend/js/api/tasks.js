import { api } from './client.js';

// --- Team implementation: Tasks API ---
// API scaffold/specification: Toon van Berkel
// Task API implementation/updates: Liam Plokkaar
// Source branch: taskpage
// Integration by Toon van Berkel:
// - retained the shared API client
// - aligned endpoints with the current development structure

export const tasksApi = {
    list() {
        // The backend returns open tasks with their public status and image metadata.
        // TODO [OPTIONAL]: Add filtering and pagination if the list grows.
        return api.get('/tasks');
    },

    view(taskId) {
        // The backend limits task detail to authenticated users and open tasks.
        return api.get(`/tasks/view?task_id=${encodeURIComponent(taskId)}`);
    },

    mine() {
        // The backend derives the creator from the authenticated session.
        return api.get('/tasks/mine');
    },

    create(payload) {
        // The server owns pending status and reward approval; FormData also carries task images.
        return payload instanceof FormData ? api.postForm('/tasks/create', payload) : api.post('/tasks/create', payload);
}
};
