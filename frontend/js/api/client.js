/*
    Uses the Herd backend during local frontend development and the relative
    /api path after deployment.

    Keep this logic aligned with the documented frontend and backend URLs.
*/
const isLocal =
    window.location.hostname === 'localhost' ||
    window.location.hostname === '127.0.0.1';

const API_URL = isLocal
    ? 'https://doofenschmirtz.test'
    : '/api';


/*
    Sends API requests on behalf of feature-specific modules.

    This client centralizes credentials, JSON handling and API error handling so
    modules such as auth.js only need to define their own endpoints and data.
    Backend API responses are expected to contain JSON.
*/
async function request(path, options = {}) {
    const response = await fetch(`${API_URL}${path}`, {
        credentials: 'include',

        ...options,

        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        }
    });

    const data = await response.json();

    if (!response.ok) {
        throw new Error(
            data.message ?? 'API request failed'
        );
    }

    return data;
}


export const api = {
    get(path) {
        return request(path, {
            method: 'GET'
        });
    },

    post(path, body = {}) {
        return request(path, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },

    put(path, body = {}) {
        return request(path, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    },

    delete(path) {
        return request(path, {
            method: 'DELETE'
        });
    }
};