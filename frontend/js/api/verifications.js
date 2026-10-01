import { api } from './client.js';

export const verificationsApi = {
    creatorQueue() { 
        // The backend returns only submissions belonging to tasks owned by the current user.
        return api.get('/verifications/creator'); 
    },
    creatorReview(payload) { 
        // The backend enforces creator ownership, state transitions and rewards.
        return api.post('/verifications/creator/review', payload); 
    },
    communityQueue() { 
        // The backend decides eligibility and excludes creators, completers and prior reviewers.
        return api.get('/verifications/community'); 
    },
    communityView(submissionId) { 
        // Optional detail endpoint; the queue currently includes the evidence needed by the UI.
        return api.get(`/verifications/community/view?submission_id=${encodeURIComponent(submissionId)}`); 
    },
    communityReview(payload) { 
        // Community review is secondary and cannot complete the task again.
        return api.post('/verifications/community/review', payload); 
    },
    requestAppeal(payload) { 
        // Request an admin third-party check for an eligible creator rejection.
        return api.post('/appeals/request', payload); 
    },
    acceptRejection(payload) { 
        // Let the submitter accept the creator rejection without receiving points.
        return api.post('/appeals/accept-rejection', payload); 
    },
    appeals() { 
        // Fetch pending third-party checks for the active admin.
        return api.get('/appeals'); 
    },
    appealView(appealId) { 
        // Fetch appeal details subject to backend privacy and role checks.
        return api.get(`/appeals/view?appeal_id=${encodeURIComponent(appealId)}`); 
    },
    reviewAppeal(payload) { 
        // Only the backend-authorized admin can finalize the third-party check.
        return api.post('/appeals/review', payload); 
    }
};
