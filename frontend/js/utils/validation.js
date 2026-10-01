export function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

export function validatePassword(password) {
    return password.length >= 8
        && /[A-Z]/.test(password)
        && /[a-z]/.test(password)
        && /[0-9]|[^A-Za-z0-9]/.test(password);
}

export function getPasswordRequirementStates(password) {
    return {
        length: password.length >= 8,
        case: /[A-Z]/.test(password) && /[a-z]/.test(password),
        'number-special': /[0-9]|[^A-Za-z0-9]/.test(password)
    };
}
