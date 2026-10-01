import { api } from './client.js';

export const activityApi = {
    list() { 
        // The backend derives both activity and balance from point_transactions.
        return api.get('/activity'); 
    }
};
