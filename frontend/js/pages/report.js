import {
    closeAnimatedModal,
    clearFieldError,
    initAuthUI,
    openAnimatedModal,
    setFieldError,
    showFieldTooltip,
    updateSubmitButton
} from '../utils/ui.js';
import { validateEmail } from '../utils/validation.js';

const reportForm = document.querySelector('#report-form');
const reportEmail = document.querySelector('#report-email');
const reportSubject = document.querySelector('#report-subject');
const reportMessage = document.querySelector('#report-message');
const successModal = document.querySelector('#report-success-modal');
const closeButton = successModal.querySelector('[data-report-success-close]');
const reportParams = new URLSearchParams(window.location.search);
const reportTimingKey = 'doofenschmirtz-last-report-time';
const reportCooldownKey = 'doofenschmirtz-report-cooldown-until';

initAuthUI();

function clearReportFieldError(field, errorId) {
    clearFieldError(field, errorId);
}

const subject = reportParams.get('subject');
const email = reportParams.get('email');

if (['login', 'register', 'other'].includes(subject)) {
    reportSubject.value = subject;
}

if (email) {
    reportEmail.value = email;
}

function updateReportButton() {
    const valid = validateEmail(reportEmail.value.trim())
        && Boolean(reportSubject.value)
        && Boolean(reportMessage.value.trim());

    updateSubmitButton(reportForm, valid);
}

reportEmail.addEventListener('input', () => {
    if (validateEmail(reportEmail.value.trim())) {
        clearReportFieldError(reportEmail, 'report-email-error');
    }
    updateReportButton();
});

reportSubject.addEventListener('change', () => {
    if (reportSubject.value) {
        clearReportFieldError(reportSubject, 'report-subject-error');
    }
    updateReportButton();
});

reportMessage.addEventListener('input', () => {
    if (reportMessage.value.trim()) {
        clearReportFieldError(reportMessage, 'report-message-error');
    }
    updateReportButton();
});

updateReportButton();

function closeReportSuccessModal() {
    closeAnimatedModal(successModal);
}

function showReportModal(title, message, type = 'success') {
    const icon = successModal.querySelector('#report-success-icon');

    successModal.querySelector('#report-success-title').textContent = title;
    successModal.querySelector('.modal-panel p').textContent = message;
    successModal.classList.toggle('report-waiting', type === 'waiting');
    icon.classList.toggle('icon-check', type !== 'waiting');
    icon.classList.toggle('icon-time', type === 'waiting');
    openAnimatedModal(successModal);
    closeButton.focus();
}

reportForm.addEventListener('submit', (event) => {
    event.preventDefault();

    let valid = true;

    if (!validateEmail(reportEmail.value.trim())) {
        setFieldError(
            reportEmail,
            'report-email-error',
            reportEmail.value.trim() ? 'Email is invalid' : 'Email address required'
        );
        showFieldTooltip(reportEmail);
        valid = false;
    }

    if (!reportSubject.value) {
        setFieldError(reportSubject, 'report-subject-error', 'Subject required');
        showFieldTooltip(reportSubject);
        valid = false;
    }

    if (!reportMessage.value.trim()) {
        setFieldError(reportMessage, 'report-message-error', 'Message required');
        showFieldTooltip(reportMessage);
        valid = false;
    }

    if (!valid) {
        return;
    }

    const now = Date.now();
    const lastReportTime = Number(localStorage.getItem(reportTimingKey) || 0);
    const cooldownUntil = Number(localStorage.getItem(reportCooldownKey) || 0);

    if (cooldownUntil > now) {
        showReportModal(
            'Please wait',
            'You are temporarily blocked from sending another report. Please try again in one minute.',
            'waiting'
        );
        return;
    }

    if (lastReportTime && now - lastReportTime < 10000) {
        localStorage.setItem(reportCooldownKey, String(now + 60000));
        showReportModal(
            'Please wait',
            'You sent a report too recently. Please try again in one minute.',
            'waiting'
        );
        return;
    }

    localStorage.setItem(reportTimingKey, String(now));
    reportMessage.value = '';
    showReportModal(
        'Report sent',
        "Thank you for your report. We'll take a look at the problem."
    );
});

closeButton.addEventListener('click', closeReportSuccessModal);
