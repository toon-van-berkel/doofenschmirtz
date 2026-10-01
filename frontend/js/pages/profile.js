import { authApi } from '../api/index.js';

// --- Profile integration ---
// JavaScript scaffold/base implementation: Toon van Berkel
// Profile markup/UI: DianaLeli
// Source branch for UI: profile
// Integration by Toon van Berkel:
// - connected Diana's UI to current-user/auth
// - added masked email handling
// - preserved logout behaviour
const username = document.querySelector('#username');
const maskedEmail = document.querySelector('#masked-email');
const revealForm = document.querySelector('#reveal-email-form');
const errorMessage = document.querySelector('#error-message');
const logoutButton = document.querySelector('#logout-button');

function maskEmail(email) {
    const [localPart, domain] = email.split('@');
    if (!localPart || !domain) return 'Unavailable';

    const visible = localPart.slice(0, 1);
    return `${visible}${'*'.repeat(Math.max(localPart.length - 1, 1))}@${domain}`;
}

authApi.currentUser()
    .then((response) => {
        const user = response.user;
        username.textContent = user.username;
        maskedEmail.textContent = maskEmail(user.email);
    })
    .catch(() => {
        window.location.href = './login.html';
    });

revealForm.addEventListener('submit', (event) => {
    event.preventDefault();
    // No secure password-verification endpoint exists in the current backend.
    // Do not perform frontend-only password validation or reveal the email.
    errorMessage.hidden = false;
    errorMessage.textContent = 'Email reveal is not available yet.';
});

logoutButton.addEventListener('click', async () => {
    await authApi.logout();
    window.location.href = './login.html';
});
