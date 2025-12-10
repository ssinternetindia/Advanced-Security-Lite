/**
 * Advanced Security Lite - Custom Login JavaScript
 * Handles form submission and interactions for the split-screen login design
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginform');
    const submitBtn = document.getElementById('wp-submit');
    const usernameInput = document.getElementById('user_login');
    const passwordInput = document.getElementById('user_pass');
    
    if (!loginForm) return;
    
    // Handle form submission
    loginForm.addEventListener('submit', function(e) {
        // Basic validation
        if (!usernameInput.value.trim()) {
            e.preventDefault();
            showError('Please enter your username or email address.');
            usernameInput.focus();
            return false;
        }
        
        if (!passwordInput.value) {
            e.preventDefault();
            showError('Please enter your password.');
            passwordInput.focus();
            return false;
        }
        
        // Show loading state
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing in...';
            submitBtn.classList.add('loading');
        }
        
        // Allow form to submit normally
        return true;
    });
    
    // Handle input focus effects
    const inputs = document.querySelectorAll('.login-input');
    inputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Check if input has value on load
        if (input.value) {
            input.parentElement.classList.add('focused');
        }
    });
    
    // Handle remember me checkbox styling
    const rememberCheckbox = document.getElementById('rememberme');
    if (rememberCheckbox) {
        rememberCheckbox.addEventListener('change', function() {
            this.parentElement.classList.toggle('checked', this.checked);
        });
    }
    
    // Auto-focus first empty input
    if (usernameInput && !usernameInput.value) {
        usernameInput.focus();
    } else if (passwordInput && !passwordInput.value) {
        passwordInput.focus();
    }
    
    // Handle Enter key in inputs
    inputs.forEach(function(input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (input === usernameInput && passwordInput) {
                    passwordInput.focus();
                } else {
                    loginForm.submit();
                }
            }
        });
    });
    
    /**
     * Show error message
     */
    function showError(message) {
        removeExistingMessages();
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'login-error';
        errorDiv.textContent = message;
        
        const formTitle = document.querySelector('.login-form-title');
        if (formTitle) {
            formTitle.parentNode.insertBefore(errorDiv, formTitle.nextSibling);
        }
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }
    
    /**
     * Show info message
     */
    function showMessage(message) {
        removeExistingMessages();
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'login-message';
        messageDiv.textContent = message;
        
        const formTitle = document.querySelector('.login-form-title');
        if (formTitle) {
            formTitle.parentNode.insertBefore(messageDiv, formTitle.nextSibling);
        }
        
        // Auto-remove after 3 seconds
        setTimeout(function() {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 3000);
    }
    
    /**
     * Remove existing error/message elements
     */
    function removeExistingMessages() {
        const existingErrors = document.querySelectorAll('.login-error, .login-message');
        existingErrors.forEach(function(element) {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        });
    }
    
    // Handle responsive behavior
    function handleResize() {
        const container = document.querySelector('.login-split-container');
        if (container) {
            if (window.innerWidth <= 768) {
                container.classList.add('mobile');
            } else {
                container.classList.remove('mobile');
            }
        }
    }
    
    // Initial resize check
    handleResize();
    
    // Listen for window resize
    window.addEventListener('resize', handleResize);
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Add smooth entrance animation
    const loginContainer = document.querySelector('.login-split-container');
    if (loginContainer) {
        loginContainer.style.opacity = '0';
        loginContainer.style.transform = 'translateY(20px)';
        
        setTimeout(function() {
            loginContainer.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            loginContainer.style.opacity = '1';
            loginContainer.style.transform = 'translateY(0)';
        }, 100);
    }
});

/**
 * Smooth scroll to error if form validation fails
 */
function scrollToError() {
    const errorElement = document.querySelector('.login-error');
    if (errorElement) {
        errorElement.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
    }
}