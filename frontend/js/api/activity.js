import { api } from './client.js';

export const activityApi = {
    list() { 
        // TODO [ACTIVITY API]: Call GET /activity for the authenticated user.
        // The response should contain point_transactions activity plus a balance calculated from SUM(amount),
        // with future pagination/filter query parameters.
        return api.get('/activity'); 
    }
};
