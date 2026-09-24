const modal = document.querySelector('#service-modal');
const serverErrorModal = document.querySelector('#server-error-modal');
const successModal = document.querySelector('#success-modal');
const loginSuccessModal = document.querySelector('#login-success-modal');
const fieldTooltipTimers = new Map();

function startModalBurst(target) {
    target.classList.remove('burst-active');
    void target.offsetWidth;
    target.classList.add('burst-active');

    const ray = target.querySelector('.error-symbol__ray');
    ray.addEventListener('animationend', () => {
        target.classList.remove('burst-active');
    }, { once: true });
}

export function openAnimatedModal(target, firstOpen = false) {
    if (!target) {
        return;
    }

    target.hidden = false;
    target.classList.remove('is-active', 'is-closing');
    target.classList.toggle('first-open', firstOpen);
    target.classList.add('is-opening');

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        target.classList.remove('is-opening');
        target.classList.add('is-active');
        return;
    }

    const panel = target.querySelector('.server-error-modal__panel, .modal-panel');

    panel.addEventListener('animationend', (event) => {
        if (event.animationName !== 'modalPopIn') {
            return;
        }

        target.classList.remove('is-opening');
        target.classList.add('is-active');
        startModalBurst(target);
    }, { once: true });
}

export function closeAnimatedModal(target) {
    if (!target || target.hidden || target.classList.contains('is-closing')) {
        return;
    }

    target.classList.remove('is-active', 'is-opening', 'burst-active');

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        target.hidden = true;
        return;
    }

    target.classList.add('is-closing');

    const panel = target.querySelector('.server-error-modal__panel, .modal-panel');

    panel.addEventListener('animationend', (event) => {
        if (event.animationName !== 'modalPopOut') {
            return;
        }

        target.hidden = true;
        target.classList.remove('is-closing');
    }, { once: true });
}

export function showServerError(title = 'Connection failed', message = 'Something went wrong while connecting to the server. Please try again later.') {
    if (!serverErrorModal) {
        return;
    }

    serverErrorModal.querySelector('#server-error-title').textContent = title;
    serverErrorModal.querySelector('.server-error-modal__panel p').textContent = message;
    openAnimatedModal(serverErrorModal);
    serverErrorModal.querySelector('[data-server-error-close]').focus();
}

export function showLoginError() {
    showServerError('Login failed', 'Password or email is incorrect.');
}

export function showServiceModal() {
    if (!modal) {
        return;
    }

    openAnimatedModal(modal);
    modal.querySelector('[data-modal-close]').focus();
}

export function showSuccessModal() {
    if (!successModal) {
        return;
    }

    openAnimatedModal(successModal);
    successModal.querySelector('[data-success-close]').focus();
}

export function showLoginSuccessModal() {
    if (!loginSuccessModal) {
        return;
    }

    openAnimatedModal(loginSuccessModal);
    loginSuccessModal.querySelector('[data-login-success-close]').focus();
}

export function setFieldError(input, errorId, message) {
    const error = document.querySelector(`#${errorId}`);
    const container = input.closest('.input-container');
    const fieldGroup = input.closest('.field-group');

    input.classList.add('input-error');
    input.setAttribute('aria-invalid', 'true');
    input.setAttribute('aria-describedby', errorId);
    container.classList.add('input-container--error');
    fieldGroup.classList.add('field-group--error');
    error.textContent = message;
}

export function clearFieldError(input, errorId) {
    const error = document.querySelector(`#${errorId}`);
    const container = input.closest('.input-container');
    const fieldGroup = input.closest('.field-group');

    input.classList.remove('input-error');
    input.removeAttribute('aria-invalid');
    input.removeAttribute('aria-describedby');
    container.classList.remove('input-container--error');
    fieldGroup.classList.remove('field-group--error');
    error.textContent = '';
}

function getFieldTooltip(input) {
    const inputWrapper = input.closest('.field-input-wrap');

    if (inputWrapper) {
        return inputWrapper.querySelector('.field-error');
    }

    return input.closest('.field-group').querySelector('.field-error');
}

export function closeFieldTooltip(input) {
    const tooltip = getFieldTooltip(input);

    if (!tooltip || !tooltip.classList.contains('field-error--visible')) {
        return;
    }

    window.clearTimeout(fieldTooltipTimers.get(tooltip));

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        tooltip.classList.remove('field-error--visible', 'is-closing');
        return;
    }

    tooltip.classList.add('is-closing');
    tooltip.addEventListener('animationend', () => {
        tooltip.classList.remove('field-error--visible', 'is-closing');
    }, { once: true });
}

export function showFieldTooltip(input) {
    const tooltip = getFieldTooltip(input);

    if (!tooltip) {
        return;
    }

    window.clearTimeout(fieldTooltipTimers.get(tooltip));
    tooltip.classList.remove('is-closing');
    tooltip.classList.add('field-error--visible');
    fieldTooltipTimers.set(tooltip, window.setTimeout(() => {
        closeFieldTooltip(input);
    }, 1500));
}

export function showInvalidFieldTooltips(form) {
    form.querySelectorAll('.field-group--error').forEach((fieldGroup) => {
        const invalidInput = fieldGroup.querySelector('input');

        if (invalidInput) {
            showFieldTooltip(invalidInput);
        }
    });
}

export function updateSubmitButton(form, valid) {
    form.querySelector('.primary-button').classList.toggle('is-form-invalid', !valid);
}

export function updatePasswordRequirements(password, getStates) {
    const requirements = document.querySelectorAll('.password-requirement');
    const states = getStates(password);

    requirements.forEach((requirement) => {
        const isValid = states[requirement.dataset.requirement];
        const wasValid = requirement.classList.contains('is-valid');

        requirement.classList.toggle('is-valid', isValid);

        if (isValid && !wasValid) {
            requirement.querySelector('.requirement-check').classList.remove('is-animating');
            void requirement.offsetWidth;
            requirement.querySelector('.requirement-check').classList.add('is-animating');
        }
    });
}

export function initAuthUI() {
    document.querySelectorAll('[data-social]').forEach((button) => {
        button.addEventListener('click', showServiceModal);
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => closeAnimatedModal(modal));
    });

    if (serverErrorModal) {
        serverErrorModal.querySelector('[data-server-error-close]')?.addEventListener('click', () => {
            closeAnimatedModal(serverErrorModal);
        });

        serverErrorModal.addEventListener('click', (event) => {
            if (event.target === serverErrorModal) {
                closeAnimatedModal(serverErrorModal);
            }
        });
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeAnimatedModal(modal);
            }
        });
    }

    if (successModal) {
        successModal.querySelector('[data-success-close]')?.addEventListener('click', () => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                window.location.href = './login.html';
                return;
            }

            const panel = successModal.querySelector('.modal-panel');
            panel.addEventListener('animationend', (event) => {
                if (event.animationName === 'modalPopOut') {
                    window.location.href = './login.html';
                }
            }, { once: true });
            closeAnimatedModal(successModal);
        });
    }

    if (loginSuccessModal) {
        loginSuccessModal.querySelector('[data-login-success-close]')?.addEventListener('click', () => {
            window.location.href = './index.html';
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (modal && !modal.hidden) closeAnimatedModal(modal);
        if (serverErrorModal && !serverErrorModal.hidden) closeAnimatedModal(serverErrorModal);
        if (successModal && !successModal.hidden) closeAnimatedModal(successModal);
        if (loginSuccessModal && !loginSuccessModal.hidden) closeAnimatedModal(loginSuccessModal);
    });
}

export function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach(async (button) => {
        const input = document.querySelector(`#${button.dataset.passwordToggle}`);
        const lockWrapper = button.closest('.password-input').querySelector('.password-lock');
        const lockMarkup = await fetch('./assets/icons/animated-lock.svg').then((response) => response.text());

        lockWrapper.innerHTML = lockMarkup;
        const lock = lockWrapper.querySelector('.lock-icon');

        button.addEventListener('click', () => {
            if (input.classList.contains('is-switching')) return;

            const isOpen = input.type === 'password';
            const eye = button.querySelector('.icon');
            input.classList.add('is-switching');

            const switchPassword = () => {
                input.type = isOpen ? 'text' : 'password';
                eye.classList.toggle('icon-eye-show', isOpen);
                eye.classList.toggle('icon-eye-hidden', !isOpen);
                lock.classList.toggle('is-open', isOpen);
                lock.classList.toggle('is-closed', !isOpen);
                button.setAttribute('aria-label', isOpen ? 'Hide password' : 'Show password');
            };

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                switchPassword();
                input.classList.remove('is-switching');
                return;
            }

            window.setTimeout(switchPassword, 90);
            window.setTimeout(() => input.classList.remove('is-switching'), 180);
        });
    });
}
