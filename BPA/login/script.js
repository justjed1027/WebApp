// login.js
document.getElementById('loginForm').addEventListener('submit', function(e) {
e.preventDefault();
const email = document.getElementById('email').value.trim();
const password = document.getElementById('password').value.trim();


if (!email || !password) {
alert("Please fill out both fields.");
return;
}


alert(`Welcome back, ${email}!`);
});