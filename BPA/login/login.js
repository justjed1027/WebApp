// login.js - Updated for Google OAuth and Email Toggle

/**
 * Toggle the email/password form visibility
 */
function toggleEmailForm() {
    const emailForm = document.getElementById('emailLoginForm');
    
    if (emailForm.classList.contains('hidden')) {
        emailForm.classList.remove('hidden');
        // Focus on the email input
        setTimeout(() => {
            document.getElementById('email').focus();
        }, 100);
    } else {
        emailForm.classList.add('hidden');
    }
    
    return false; // Prevent default button action
}

/**
 * Toggle password visibility
 */
function togglePassword() {
    const passwordInput = document.getElementById('password');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
    } else {
        passwordInput.type = 'password';
    }
}

/**
 * Handle Google Sign-In button click
 * This initiates the full OAuth redirect flow where user picks their account
 */
function googleSignIn() {
    // Google OAuth 2.0 endpoints
    const clientId = 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com';
    const redirectUri = window.location.origin + '/WebApp/BPA/login/google-callback.php';
    const scope = 'email profile openid';
    
    // Build the authorization URL
    const authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' + new URLSearchParams({
        client_id: clientId,
        redirect_uri: redirectUri,
        response_type: 'code',
        scope: scope,
        access_type: 'online',
        prompt: 'select_account' // Force account selection
    });
    
    // Redirect to Google's account selection page
    window.location.href = authUrl;
}

// Form validation (keep existing functionality)
const emailForm = document.getElementById('emailLoginForm');
if (emailForm) {
    emailForm.addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();

        if (!email || !password) {
            e.preventDefault();
            alert("Please fill out both fields.");
            return false;
        }
        
        // Form will submit normally to PHP
    });
}
