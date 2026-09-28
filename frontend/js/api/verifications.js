import { api } from './client.js';

export const verificationsApi = {
    creatorQueue() { 
        // TODO [CREATOR VERIFICATION]: Fetch the creator's pending review queue from GET /verifications/creator.
        // Authorization and eligibility remain server-side.
        return api.get('/verifications/creator'); 
    },
    creatorReview(payload) { 
        // TODO [CREATOR VERIFICATION]: POST submission_id, decision, and optional comment to /verifications/creator/review.
        // The backend must enforce task ownership, state transitions, and the one-time +10 creator reward.
        return api.post('/verifications/creator/review', payload); 
    },
    communityQueue() { 
        // TODO [COMMUNITY VERIFICATION]: Fetch eligible community submissions from GET /verifications/community.
        // The backend must exclude task/submission creators and prior reviewers.
        return api.get('/verifications/community'); 
    },
    communityView(submissionId) { 
        // TODO [COMMUNITY VERIFICATION]: Fetch eligible evidence from GET /verifications/community/view?submission_id=.
        return api.get(`/verifications/community/view?submission_id=${encodeURIComponent(submissionId)}`); 
    },
    communityReview(payload) { 
        // TODO [COMMUNITY VERIFICATION]: POST submission_id, Yes/No decision, and optional comment.
        // The backend awards +5 once and must not alter task/submission completion state.
        return api.post('/verifications/community/review', payload); 
    },
    requestAppeal(payload) { 
        // TODO [APPEAL]: POST submission_id and appeal reason to /appeals/request for an eligible rejected submission.
        return api.post('/appeals/request', payload); 
    },
    acceptRejection(payload) { 
        // TODO [APPEAL]: POST submission_id to /appeals/accept-rejection and finalize the rejection without points.
        return api.post('/appeals/accept-rejection', payload); 
    },
    appeals() { 
        // TODO [APPEAL]: Fetch the eligible appeal queue from GET /appeals.
        return api.get('/appeals'); 
    },
    appealView(appealId) { 
        // TODO [APPEAL]: Fetch one appeal from GET /appeals/view?appeal_id= with server-side privacy checks.
        return api.get(`/appeals/view?appeal_id=${encodeURIComponent(appealId)}`); 
    },
    reviewAppeal(payload) { 
        // TODO [APPEAL]: POST appeal_id, decision, and comment to /appeals/review.
        // The backend must enforce reviewer eligibility and the final state/point rules.
        return api.post('/appeals/review', payload); 
    }
};
