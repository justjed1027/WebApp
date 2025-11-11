
function togglePassword() {
    var passwordField = document.getElementById("password");
    const eyeopen = document.getElementById("eye-open");
    const eyeclose = document.getElementById("eye-slash");
    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeopen.classList.remove("hidden");
        eyeclose.classList.add("hidden");
    } else {
        passwordField.type = "password";
        eyeclose.classList.remove("hidden");
        eyeopen.classList.add("hidden");
    }
}

// Google OAuth trigger (mirrors login)
function googleSignIn() {
    const clientId = 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com';
    const redirectUri = window.location.origin + '/WebApp/BPA/login/google-callback.php';
    const scope = 'email profile openid';
    const authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' + new URLSearchParams({
        client_id: clientId,
        redirect_uri: redirectUri,
        response_type: 'code',
        scope: scope,
        access_type: 'online',
        prompt: 'select_account'
    });
    window.location.href = authUrl;
}
