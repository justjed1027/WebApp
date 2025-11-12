<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Reset password</title>
<link rel="stylesheet" href="forgot.css" />
</head>
<body>
<div class="split-container">
  <div class="left-panel">
    <div class="welcome-content">
      <div class="brand-logo">SkillShare</div>
      <h1>Reset password</h1>
      <p class="subtitle">Enter your email and we'll send you a verification code.</p>

      <form id="forgotForm" action="verify.php" method="GET" class="email-form">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" required />

        <button type="submit" class="signin-btn">Send verification code</button>
      </form>

      <p class="helper-link">Remembered your password? <a href="../login/login.php">log in</a></p>
    </div>
  </div>

  <div class="right-panel"></div>
</div>
</body>
</html>
