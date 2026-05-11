// ==============================================
// FILE: script.js
// ==============================================
// Global JavaScript for Admin Password Manager
// ==============================================

// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    // Add sidebar toggle button to navbar
    const navbar = document.querySelector('.navbar-custom');
    if (navbar && window.innerWidth <= 768) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-link text-white me-2 sidebar-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
        navbar.insertBefore(toggleBtn, navbar.firstChild);
    }
    
    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Password strength check function for any password input
    document.querySelectorAll('input[type="password"]').forEach(input => {
        input.addEventListener('input', function() {
            const strength = checkPasswordStrengthBase(this.value);
            const strengthContainer = this.closest('.mb-3')?.querySelector('.password-strength');
            if (strengthContainer) {
                let bar = strengthContainer.querySelector('.progress-bar');
                let text = strengthContainer.querySelector('small');
                if (!bar) {
                    strengthContainer.innerHTML = `
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted"></small>
                    `;
                    bar = strengthContainer.querySelector('.progress-bar');
                    text = strengthContainer.querySelector('small');
                }
                bar.style.width = strength.percent + '%';
                bar.className = `progress-bar bg-${strength.color}`;
                text.textContent = strength.message;
                text.className = `text-${strength.color}`;
            }
        });
    });
});

function checkPasswordStrengthBase(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    
    const percent = (score / 5) * 100;
    let color = 'danger';
    let message = 'Weak';
    
    if (score >= 4) {
        color = 'success';
        message = 'Strong';
    } else if (score >= 3) {
        color = 'warning';
        message = 'Medium';
    } else if (score >= 2) {
        color = 'info';
        message = 'Fair';
    }
    
    return { score, percent, color, message };
}

// Copy to clipboard utility
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text);
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
    showNotification('Copied to clipboard!', 'success');
}

// Show notification toast
function showNotification(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// Generate secure random password
function generateSecurePassword(length = 16) {
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+{}[]|:;<>,.?';
    let password = '';
    const array = new Uint8Array(length);
    crypto.getRandomValues(array);
    for (let i = 0; i < length; i++) {
        password += charset[array[i] % charset.length];
    }
    return password;
}

// Validate form inputs
function validatePasswordForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const name = form.querySelector('[name="name"]')?.value;
    const username = form.querySelector('[name="username"]')?.value;
    const password = form.querySelector('[name="password"]')?.value;
    
    if (!name || !username || !password) {
        showNotification('Please fill all required fields', 'danger');
        return false;
    }
    
    if (password.length < 4) {
        showNotification('Password is too short', 'danger');
        return false;
    }
    
    return true;
}

// Confirm destructive actions
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Session timeout warning
let sessionTimeoutWarning;
function startSessionTimer(timeoutSeconds) {
    if (sessionTimeoutWarning) clearTimeout(sessionTimeoutWarning);
    
    // Show warning 1 minute before timeout
    sessionTimeoutWarning = setTimeout(() => {
        showNotification('Your session will expire soon due to inactivity', 'warning');
    }, (timeoutSeconds - 60) * 1000);
}

// Initialize session timer if on dashboard or vault
if (window.location.pathname.includes('dashboard.php') || 
    window.location.pathname.includes('vault.php')) {
    const timeout = <?php echo defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 1800; ?>;
    startSessionTimer(timeout);
    
    // Reset timer on user activity
    const resetTimer = () => {
        if (sessionTimeoutWarning) clearTimeout(sessionTimeoutWarning);
        startSessionTimer(timeout);
    };
    
    document.addEventListener('mousemove', resetTimer);
    document.addEventListener('keypress', resetTimer);
    document.addEventListener('click', resetTimer);
}