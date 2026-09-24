import { authApi } from '../api/index.js';
import {
    clearFieldError,
    closeFieldTooltip,
    initAuthUI,
    initPasswordToggles,
    setFieldError,
    showInvalidFieldTooltips,
    showServerError,
    showSuccessModal,
    updatePasswordRequirements,
    updateSubmitButton
} from './ui.js';
import { getPasswordRequirementStates, validateEmail, validatePassword } from './validation.js';

const registerForm = document.querySelector('#register-form');
const terms = document.querySelector('#terms');

initAuthUI();
initPasswordToggles();

function updateRegisterButton() {
    const password = registerForm.password.value;
    const valid = Boolean(registerForm.fullName.value.trim())
        && validateEmail(registerForm.email.value.trim())
        && validatePassword(password)
        && terms.checked;

    updateSubmitButton(registerForm, valid);
}

function validateRegisterForm(form) {
    let valid = true;

    if (!form.fullName.value.trim()) {
        setFieldError(form.fullName, 'register-name-error', 'Full name required');
        valid = false;
    } else {
        clearFieldError(form.fullName, 'register-name-error');
    }

    if (!form.email.value.trim()) {
        setFieldError(form.email, 'register-email-error', 'Email address required');
        valid = false;
    } else if (!validateEmail(form.email.value.trim())) {
        setFieldError(form.email, 'register-email-error', 'Invalid email address');
        valid = false;
    } else {
        clearFieldError(form.email, 'register-email-error');
    }

    if (!validatePassword(form.password.value)) {
        setFieldError(form.password, 'register-password-error', 'Password requirements not met');
        valid = false;
    } else {
        clearFieldError(form.password, 'register-password-error');
    }

    const termsError = document.querySelector('#terms-error');
    const termsGroup = terms.closest('.field-group');

    if (!terms.checked) {
        terms.setAttribute('aria-invalid', 'true');
        terms.setAttribute('aria-describedby', 'terms-error');
        termsGroup.classList.add('field-group--error');
        termsError.textContent = 'Please accept the terms';
        valid = false;
    } else {
        terms.removeAttribute('aria-invalid');
        terms.removeAttribute('aria-describedby');
        termsGroup.classList.remove('field-group--error');
        termsError.textContent = '';
    }

    return valid;
}

registerForm.querySelectorAll('input').forEach((input) => {
    input.addEventListener('input', () => {
        const error = input.closest('.auth-form').querySelector(`#${input.id}-error`);
        const container = input.closest('.input-container');
        const emailIsValid = input.type !== 'email' || validateEmail(input.value.trim());

        if (error && container && input.value.trim() && emailIsValid) {
            clearFieldError(input, `${input.id}-error`);
            closeFieldTooltip(input);
        }

        if (input.id === 'register-password') {
            updatePasswordRequirements(input.value, getPasswordRequirementStates);
        }

        updateRegisterButton();
    });
});

terms.addEventListener('change', () => {
    if (terms.checked) {
        terms.removeAttribute('aria-invalid');
        terms.removeAttribute('aria-describedby');
        terms.closest('.field-group').classList.remove('field-group--error');
        document.querySelector('#terms-error').textContent = '';
        closeFieldTooltip(terms);
    }

    updateRegisterButton();
});

updateRegisterButton();
updatePasswordRequirements(registerForm.password.value, getPasswordRequirementStates);

registerForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!validateRegisterForm(registerForm)) {
        showInvalidFieldTooltips(registerForm);
        return;
    }

    try {
        await authApi.register(
            registerForm.fullName.value,
            registerForm.email.value,
            registerForm.password.value
        );
        registerForm.reset();
        showSuccessModal();
    } catch (error) {
        if (!error.status || error.status >= 500) {
            showServerError();
        }
    }
});
