import { authApi } from '../api/index.js';
//Import the Api that lets you call user's point balance
//then: push said point balance into the aforementioned variable's HTML DOM element

const username = document.querySelector('#username');
const userpoints = document.querySelector('#current-points-user')

authApi.currentUser()
    .then((response) => {
        username.textContent = response.user.username;
        userpoints.textContent = `You have ${response.user.username} points gaga.`
    })
    .catch(() => {
        window.location.href = './login.html';
    });

