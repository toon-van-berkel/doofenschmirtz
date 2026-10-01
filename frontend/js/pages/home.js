import { authApi, activityApi } from '../api/index.js';

const username = document.querySelector('#username');
const balance = document.querySelector('#points-balance');

authApi.currentUser()
    .then((response) => {
        username.textContent = response.user.username;
        return activityApi.list();
    })
    .then((response) => {
        if (response && balance) {
            balance.textContent = response.balance;
        }
    })
    .catch(() => {
        window.location.href = './login.html';
    });
