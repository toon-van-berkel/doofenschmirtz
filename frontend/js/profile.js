import { authApi } from './api/index.js';

const username = document.querySelector('#profile-username');
const logoutButton = document.querySelector('#logout-button');

authApi.currentUser()
    .then((response) => {
        username.textContent = response.user.username;
    })
    .catch(() => {
        window.location.href = './login.html';
    });

logoutButton.addEventListener('click', async () => {
    await authApi.logout();
    window.location.href = './login.html';
});
