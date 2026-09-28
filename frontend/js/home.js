import { authApi } from './api/index.js';

const username = document.querySelector('#username');
const userpoints = document.querySelector('#current-points-user')

authApi.currentUser()
    .then((response) => {
        username.textContent = response.user.username;
        userpoints.textContent = `You have ${response.user.username} points.`
    })
    .catch(() => {
        window.location.href = './login.html';
    });
