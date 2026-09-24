import { authApi } from '../api/index.js';
import {
    clearFieldError,
    closeFieldTooltip,
    initAuthUI,
    initPasswordToggles,
    setFieldError,
    showFieldTooltip,
    showInvalidFieldTooltips,
    showLoginError,
    showLoginSuccessModal,
    showServerError,
    updateSubmitButton
} from './ui.js';
import { validateEmail } from './validation.js';

const loginForm = document.querySelector('#login-form');

initAuthUI();
initPasswordToggles();

authApi.currentUser()
    .then(() => {
        window.location.href = './index.html';
    })
    .catch(() => {
        // The user is not logged in yet.
    });

function validateLoginForm(form) {
    let valid = true;

    if (!form.email.value.trim()) {
        setFieldError(form.email, 'login-email-error', 'Email address required');
        valid = false;
    } else if (!validateEmail(form.email.value.trim())) {
        setFieldError(form.email, 'login-email-error', 'Invalid email address');
        valid = false;
    } else {
        clearFieldError(form.email, 'login-email-error');
    }

    if (!form.password.value) {
        setFieldError(form.password, 'login-password-error', 'Password required');
        valid = false;
    } else {
        clearFieldError(form.password, 'login-password-error');
    }

    return valid;
}

function updateLoginButton() {
    const valid = validateEmail(loginForm.email.value.trim()) && Boolean(loginForm.password.value);
    updateSubmitButton(loginForm, valid);
}

loginForm.querySelectorAll('input').forEach((input) => {
    input.addEventListener('input', () => {
        const error = input.closest('.auth-form').querySelector(`#${input.id}-error`);
        const container = input.closest('.input-container');
        const emailIsValid = input.type !== 'email' || validateEmail(input.value.trim());

        if (error && container && input.value.trim() && emailIsValid) {
            clearFieldError(input, `${input.id}-error`);
            closeFieldTooltip(input);
        }

        updateLoginButton();
    });
});

updateLoginButton();

loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!validateLoginForm(loginForm)) {
        showInvalidFieldTooltips(loginForm);
        return;
    }

    try {
        await authApi.login(loginForm.email.value, loginForm.password.value);
        showLoginSuccessModal();
    } catch (error) {
        if (error.status === 401) {
            showLoginError();
            return;
        }

        if (!error.status || error.status >= 500) {
            showServerError();
        }
    }
});
