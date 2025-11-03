<?php
// Page 1 — Basic Info Input
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SkillSwap — Creating your Account (1/3)</title>
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>" />
</head>
<body data-step="1">
  <div class="setup-shell">
    <div class="header">
      <div class="logo">SkillSwap</div>
    </div>

    <h1 class="step-title">Creating your Account</h1>
    <p class="subtitle">The Basics and Profile</p>

    <form id="basicInfoForm" action="page2.php" method="get" enctype="multipart/form-data" novalidate>
      <div class="form-stack">
        <div class="input-card">
          <label class="input-label" for="username">Username <span class="req">*</span></label>
          <input id="username" name="username" type="text" placeholder="Choose a unique username" required />
          <div class="hint">This name will be visible to others.</div>
          <div class="error-msg" id="usernameError" hidden>Please enter a username.</div>
        </div>

        <div class="two-cols">
          <div class="input-card">
            <span class="input-label">Profile Picture <span class="optional">optional</span></span>
            <div class="avatar-upload">
              <div class="avatar" id="avatarPreview" aria-label="Profile preview">👤</div>
              <input type="file" id="avatar" name="avatar" accept="image/*" />
            </div>
            <div class="hint">Add a clear image of yourself.</div>
          </div>
          <div class="input-card">
            <label class="input-label" for="phone">Phone Number <span class="optional">optional</span></label>
            <input id="phone" name="phone" type="tel" placeholder="e.g., (555) 123-4567" />
            <div class="hint">We may use this to receive messages from mentors.</div>
          </div>
        </div>

        <div class="input-card">
          <label class="input-label" for="bio">Biography / Summary <span class="optional">optional</span></label>
          <textarea id="bio" name="bio" placeholder="Introduce yourself to fellow learners. Add a few sentences about your background, interests, or goals."></textarea>
          <div class="hint">Share a short intro so others can get to know you.</div>
        </div>
      </div>

      <div class="nav-bar">
        <div class="spacer"></div>
        <button class="btn btn-primary" id="next-1" type="submit">Next</button>
      </div>

      <div class="progress" aria-label="Progress">
        <span class="dot active"></span><span class="dot"></span><span class="dot"></span>
      </div>
    </form>
  </div>
  <script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
</body>
</html>
