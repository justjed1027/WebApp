<?php
session_start();
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Enter verification code</title>
<link rel="stylesheet" href="forgot.css" />
</head>
<body>
<div class="split-container">
  <div class="left-panel">
    <div class="welcome-content">
      <div class="brand-logo">SkillShare</div>
      <h1>Verification code</h1>
      <p class="subtitle">We've sent a 6-digit code to <?php echo $email ? '<strong>'.$email.'</strong>' : 'your email'; ?>.</p>

      <form id="verifyForm" action="#" method="POST" class="email-form">
        <label for="code">Verification code</label>
        <input type="text" id="code" name="code" inputmode="numeric" pattern="\\d{6}" maxlength="6" placeholder="123456" required />

        <button type="submit" class="signin-btn">Verify code</button>
      </form>

      <p class="helper-link"><a href="#">Resend code</a> • <a href="../login/login.php">back to login</a></p>
    </div>
  </div>

  <div class="right-panel"></div>
</div>
</body>
</html>
