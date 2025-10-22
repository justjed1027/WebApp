
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
