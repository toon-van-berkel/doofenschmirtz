import { api } from './client.js';
export const adminApi = {
    tasks() { return api.get('/admin/tasks'); },
    view(taskId) { return api.get(`/admin/tasks/view?task_id=${encodeURIComponent(taskId)}`); },
    approve(payload) { return api.post('/admin/tasks/approve', payload); },
    reject(payload) { return api.post('/admin/tasks/reject', payload); }
};
