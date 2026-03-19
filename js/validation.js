const NAME_REGEX = /^[a-zA-Z\s'\-]{2,50}$/;
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PASSWORD_REGEX = /^(?=.*[a-z])(?=.*[A-Z]).{8,}$/;

function showError(inputId, message) {
    const input = document.getElementById(inputId);
    const errorEl = document.getElementById(inputId + '_error');
    if (input) input.classList.add('is-invalid');
    if (errorEl) errorEl.textContent = message;
}

function clearErrors(formEl) {
    formEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    formEl.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}

function validateRegisterForm(formEl) {
    clearErrors(formEl);
    let valid = true;

    const fname = formEl.fname.value.trim();
    const lname = formEl.lname.value.trim();
    const email = formEl.email.value.trim();
    const pwd = formEl.pwd.value;
    const pwd_confirm = formEl.pwd_confirm.value;

    if (!fname) {
        showError('fname', 'First name is required.');
        valid = false;
    } else if (!NAME_REGEX.test(fname)) {
        showError('fname', 'Name may only contain letters, spaces, hyphens, or apostrophes.');
        valid = false;
    }

    if (!lname) {
        showError('lname', 'Last name is required.');
        valid = false;
    } else if (!NAME_REGEX.test(lname)) {
        showError('lname', 'Name may only contain letters, spaces, hyphens, or apostrophes.');
        valid = false;
    }

    if (!email) {
        showError('email', 'Email is required.');
        valid = false;
    } else if (!EMAIL_REGEX.test(email)) {
        showError('email', 'Please enter a valid email address.');
        valid = false;
    } else if (email.length > 254) {
        showError('email', 'Email address is too long.');
        valid = false;
    }

    if (!pwd) {
        showError('pwd', 'Password is required.');
        valid = false;
    } else if (!PASSWORD_REGEX.test(pwd)) {
        showError('pwd', 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.');
        valid = false;
    }

    if (!pwd_confirm) {
        showError('pwd_confirm', 'Please confirm your password.');
        valid = false;
    } else if (pwd !== pwd_confirm) {
        showError('pwd_confirm', 'Passwords do not match.');
        valid = false;
    }
    return valid;
}

function validateLoginForm(formEl) {
    clearErrors(formEl);
    let valid = true;

    const email = formEl.email.value.trim();
    const pwd = formEl.pwd.value;

    if (!email) {
        showError('email', 'Email is required.');
        valid = false;
    } else if (!EMAIL_REGEX.test(email)) {
        showError('email', 'Please enter a valid email address.');
        valid = false;
    }

    if (!pwd) {
        showError('pwd', 'Password is required.');
        valid = false;
    }
    return valid;
}