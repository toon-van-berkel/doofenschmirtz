import { authApi } from '../../api/index.js';
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
    updateSubmitButton,
    closeAnimatedModal,
    openAnimatedModal
} from '../../utils/ui.js';
import { validateEmail } from '../../utils/validation.js';
import { showApiError } from '../../components/feedback.js';

const loginForm = document.querySelector('#login-form');
const emailInput = document.querySelector('#login-email');
const forgotPasswordButton = document.querySelector('#forgot-password-button');
const forgotPasswordModal = document.querySelector('#forgot-password-modal');
const forgotPasswordTitle = document.querySelector('#forgot-password-title');
const forgotPasswordDescription = document.querySelector('#forgot-password-description');
const forgotPasswordProviderAction = document.querySelector('#forgot-password-provider-action');
const forgotPasswordClose = document.querySelector('[data-forgot-password-close]');

function detectMailProvider(email) {
    const normalizedEmail = email.trim().toLowerCase();
    const emailParts = normalizedEmail.split('@');

    if (emailParts.length !== 2 || !emailParts[0] || !emailParts[1]) {
        return 'generic';
    }

    const domain = emailParts[1];

    // Match exact email domains so custom addresses are never incorrectly
    // treated as Gmail or Outlook accounts.
    if (['gmail.com', 'googlemail.com'].includes(domain)) {
        return 'gmail';
    }

    if (['outlook.com', 'hotmail.com', 'live.com', 'msn.com'].includes(domain)) {
        return 'outlook';
    }

    return 'generic';
}

function renderForgotPasswordState() {
    const email = emailInput.value.trim();
    const provider = detectMailProvider(email);

    // The app has no password-reset backend yet, so this helper only points
    // users to the mailbox provider associated with their login address.
    forgotPasswordProviderAction.hidden = true;
    forgotPasswordProviderAction.removeAttribute('href');

    if (!email || provider === 'generic') {
        forgotPasswordTitle.textContent = 'Check your email';
        forgotPasswordDescription.textContent = email
            ? 'Open the mailbox for this email address and use your provider\'s account recovery options if needed.'
            : 'Enter your email address on the login form first and reopen this window so we can point you to the right mail provider.';
        return;
    }

    if (provider === 'gmail') {
        forgotPasswordTitle.textContent = 'Check Gmail';
        forgotPasswordDescription.textContent = 'This email address uses Gmail. Open Gmail to check your inbox or account recovery options.';
        forgotPasswordProviderAction.textContent = 'Open Gmail';
        forgotPasswordProviderAction.href = 'https://mail.google.com/';
    } else {
        forgotPasswordTitle.textContent = 'Check Outlook';
        forgotPasswordDescription.textContent = 'This email address uses Microsoft Outlook. Open Outlook to check your inbox or account recovery options.';
        forgotPasswordProviderAction.textContent = 'Open Outlook';
        forgotPasswordProviderAction.href = 'https://outlook.live.com/mail/';
    }

    forgotPasswordProviderAction.hidden = false;
}

forgotPasswordButton.addEventListener('click', () => {
    renderForgotPasswordState();
    openAnimatedModal(forgotPasswordModal);
    forgotPasswordClose.focus();
});

forgotPasswordClose.addEventListener('click', () => {
    closeAnimatedModal(forgotPasswordModal);
});

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
        // Preserve the original login-specific success modal and redirect behavior.
        showLoginSuccessModal();
    } catch (error) {
        if (error.status === 401) {
            showLoginError();
            return;
        }

        if (!error.status || error.status >= 500) {
            showServerError();
            return;
        }

        showApiError(error, { title: 'Login failed' });
    }
});
