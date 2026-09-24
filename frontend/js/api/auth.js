import { api } from './client.js';

/*  
    Authentication-specific API calls.
    Add methods here only when the corresponding backend endpoint exists.
*/
export const authApi = {
    register(username, email, password) {
        return api.post('/auth/register', {
            username,
            email,
            password
        });
    },

    login(email, password) {
        return api.post('/auth/login', {
            email,
            password
        });
    },

    currentUser() {
        return api.get('/auth/me');
    },

    logout() {
        return api.post('/auth/logout');
    }
};
