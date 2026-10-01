import { api } from './client.js';

export const submissionsApi = {
    view(submissionId) { 
        // The backend applies the privacy check before returning task and evidence data.
        return api.get(`/submissions/view?submission_id=${encodeURIComponent(submissionId)}`); 
    },
    mine() { 
        // The backend derives ownership from the authenticated session.
        return api.get('/submissions/mine'); 
    },
    create(payload) { 
        // FormData carries optional evidence images; the backend validates MIME,
        // size, ownership and rollback before making the submission permanent.
        return payload instanceof FormData ? api.postForm('/submissions/create', payload) : api.post('/submissions/create', payload);
    }
};
