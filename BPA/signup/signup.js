
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



/*document.getElementById('signupForm').addEventListener('submit', function(e) {
e.preventDefault();
const name = document.getElementById('fullName').value.trim();
const email = document.getElementById('email').value.trim();
const password = document.getElementById('password').value.trim();
const terms = document.getElementById('terms').checked;


if (!name || !email || !password || !terms) {
alert("Please fill out all fields and accept the terms.");
return;
}


alert(`Welcome, ${name}! Your account has been created.`);
});*/