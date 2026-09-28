import { api } from './client.js';

export const submissionsApi = {
    view(submissionId) { 
        // TODO [SUBMISSION VIEW]: Fetch one submission from GET /submissions/view?submission_id=.
        // The backend must return task/evidence data only when the current user is authorized to view it.
        return api.get(`/submissions/view?submission_id=${encodeURIComponent(submissionId)}`); 
    },
    mine() { 
        // TODO [SUBMISSION LIST]: Fetch submissions belonging to the currently authenticated user from GET /submissions/mine.
        return api.get('/submissions/mine'); 
    },
    create(payload) { 
        // TODO [SUBMISSION UPLOAD]: Replace the JSON payload with multipart/FormData when required evidence images are supported.
        // Files belong under uploads/submissions/; validate JPG/JPEG, PNG, and WebP MIME types and size server-side,
        // generate random storage names, keep original_filename only as metadata, and store paths in submission_images.
        // If the database transaction fails, newly uploaded files must be removed where applicable.
        return api.post('/submissions/create', payload); 
    }
};
