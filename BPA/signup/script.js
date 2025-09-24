document.getElementById('signupForm').addEventListener('submit', function(e) {
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
});