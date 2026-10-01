const FEEDBACK_ID = 'shared-feedback';
const STYLE_ID = 'shared-feedback-style';

function ensureStylesheet() {
    if (document.getElementById(STYLE_ID)) {
        return;
    }

    const stylesheet = document.createElement('link');
    stylesheet.id = STYLE_ID;
    stylesheet.rel = 'stylesheet';
    stylesheet.href = './css/components/feedback.css';
    document.head.append(stylesheet);
}
function ensureDialog() {
    let dialog = document.getElementById(FEEDBACK_ID);

    if (dialog) {
        return dialog;
    }

    // Inject one shared dialog so individual pages do not need duplicate markup.
    dialog = document.createElement('div');
    dialog.id = FEEDBACK_ID;
    dialog.className = 'shared-feedback';
    dialog.hidden = true;
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'shared-feedback-title');
    dialog.setAttribute('aria-describedby', 'shared-feedback-message');
    dialog.innerHTML = `
        <div class="shared-feedback__panel" role="document">
            <div class="shared-feedback__icon" aria-hidden="true"></div>
            <h2 class="shared-feedback__title" id="shared-feedback-title"></h2>
            <p class="shared-feedback__message" id="shared-feedback-message"></p>
            <button class="primary-button" type="button" data-feedback-close>Close</button>
        </div>
    `;

    document.body.append(dialog);
    dialog.querySelector('[data-feedback-close]').addEventListener('click', hideFeedback);

    return dialog;
}

function iconFor(type) {
    return {
        success: '✓',
        error: '!',
        warning: '!',
        info: 'i'
    }[type] ?? 'i';
}

export function showFeedback({ type = 'info', title, message } = {}) {
    ensureStylesheet();
    const dialog = ensureDialog();
    const safeType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    const closeButton = dialog.querySelector('[data-feedback-close]');

    dialog.dataset.type = safeType;
    dialog.querySelector('.shared-feedback__icon').textContent = iconFor(safeType);
    dialog.querySelector('#shared-feedback-title').textContent = title ?? '';
    dialog.querySelector('#shared-feedback-message').textContent = message ?? '';
    dialog.hidden = false;
    closeButton.focus();

}

export function hideFeedback() {
    const dialog = document.getElementById(FEEDBACK_ID);

    if (!dialog) {
        return;
    }

    dialog.hidden = true;
}

export function showApiError(error, { title = 'Request failed', fallbackMessage = 'Please try again.' } = {}) {
    const statusMessages = {
        401: 'Your session has expired. Please sign in again.',
        403: 'You do not have permission to perform this action.',
        404: 'The requested item is no longer available.',
        413: 'The uploaded file is too large.',
        500: 'The server could not complete the request.'
    };
    const message = error?.message && error.message !== 'API request failed'
        ? error.message
        : statusMessages[error?.status] ?? fallbackMessage;

    showFeedback({
        type: 'error',
        title: error?.status === 401 ? 'Session expired' : title,
        message
    });
}

export function setButtonLoading(button, loading, label = 'Working...') {
    if (!button) {
        return;
    }

    if (loading) {
        if (!button.dataset.originalText) {
            button.dataset.originalText = button.textContent;
        }
        button.disabled = true;
        button.textContent = label;
        return;
    }

    button.disabled = false;
    if (button.dataset.originalText) {
        button.textContent = button.dataset.originalText;
    }
}
