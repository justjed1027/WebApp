<?php
// Page 3 — Choose Colors and Preferences
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SkillSwap — Creating your Account (3/3)</title>
  <link rel="stylesheet" href="style.css?v=20251029" />
</head>
<body data-step="3">
  <div class="setup-shell">
    <div class="header"><div class="logo">SkillSwap</div></div>
    <h1 class="step-title">Creating your Account</h1>
    <p class="subtitle">Choose your Colors</p>

    <div class="colors-row" id="colorRow">
      <button class="swatch" data-color="#0ea5e9" style="background:#0ea5e9"></button>
      <button class="swatch" data-color="#ef4444" style="background:#ef4444"></button>
      <button class="swatch" data-color="#22c55e" style="background:#22c55e"></button>
      <button class="swatch" data-color="#06b6d4" style="background:#06b6d4"></button>
      <button class="swatch" data-color="#eab308" style="background:#eab308"></button>
      <button class="swatch" data-color="#7c3aed" style="background:#7c3aed"></button>
    </div>

    <div class="theme-previews">
      <button class="theme-card selected" data-theme="mixed" type="button">
        <div class="preview mixed"></div>
        <div>Explore Preference</div>
        <small>Default mixed mode</small>
      </button>
      <button class="theme-card" data-theme="light" type="button">
        <div class="preview light"></div>
        <div>Only Light Mode</div>
      </button>
      <button class="theme-card" data-theme="dark" type="button">
        <div class="preview dark"></div>
        <div>Only Dark Mode</div>
      </button>
    </div>

    <div class="nav-bar">
      <a class="btn" href="page2.php">Back</a>
      <div class="spacer"></div>
      <button id="finish" class="btn btn-primary" type="button">Finish</button>
    </div>

    <div class="progress" aria-label="Progress">
      <span class="dot"></span><span class="dot"></span><span class="dot active"></span>
    </div>
  </div>
  <script src="script.js?v=20251029"></script>
</body>
</html>
