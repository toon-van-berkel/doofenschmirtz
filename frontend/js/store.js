import { authApi } from './api/index.js';

// --- Team integration: Store ---
// Original feature author: LieveW
// Source branch: feat/store
// Original store implementation preserved.
// Integration by Toon van Berkel: use the current shared auth/API client.
const username = document.querySelector('#username');

authApi.currentUser()
    .then((response) => {
        username.textContent = response.user.username;
    })
    .catch(() => {
        window.location.href = './login.html';
    });
