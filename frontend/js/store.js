import { authApi } from './api/index.js';

const username = document.querySelector('#username');

authApi.currentUser()
    .then((response) => {
        username.textContent = response.user.username;
    })
    .catch(() => {
        window.location.href = './login.html';
    });
