<?php
session_start();

// Handle logout FIRST, before any includes or output
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  if (!empty($_GET['action']) && $_GET['action'] == 'logout') {
    $_SESSION = [];
    session_destroy();
    setcookie("PHPSESSID", "", time() - 3600, "/");
    header('location: ../landing/landing.php');
    exit();
  }
}

require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';





$user = new User();

//If userid exists in $_SESSION, then account is being updated. 
//Otherwise, a new account is being created. 
//We will use this page to insert and update user accounts. 
if (!empty($_SESSION['user_id'])) {

  $user->populate($_SESSION['user_id']);
} else {
  header('location: ../landing/landing.php');
}

// Page 4 — Choose Colors and Preferences
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SkillSwap — Creating your Account (4/4)</title>
  <link rel="icon" type="image/png" href="../images/skillswaplogotrans.png" />
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>" />
  <style>
    /* Construction Modal Styles */
    .construction-modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      align-items: center;
      justify-content: center;
      z-index: 10000;
      animation: fadeIn 0.2s ease-in;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .construction-modal {
      background: #ffffff;
      border-radius: 8px;
      max-width: 500px;
      width: 90%;
      padding: 0;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      border: 1px solid #e0e0e0;
      animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .construction-modal-header {
      padding: 24px 28px;
      border-bottom: 1px solid #e0e0e0;
    }

    .construction-modal-header h2 {
      font-size: 1.5rem;
      font-weight: 600;
      color: #1f2937;
      margin: 0;
    }

    .construction-modal-body {
      padding: 28px;
    }

    .construction-modal-body p {
      font-size: 1rem;
      line-height: 1.5;
      color: #4b5563;
      margin-bottom: 12px;
    }

    .construction-modal-body p:last-child {
      margin-bottom: 0;
      color: #6b7280;
      font-size: 0.95rem;
    }

    .construction-modal-footer {
      padding: 18px 28px 24px;
      text-align: center;
    }

    .construction-modal-button {
      padding: 10px 32px;
      background: #1f2937;
      color: #ffffff;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .construction-modal-button:hover {
      background: #374151;
      transform: translateY(-1px);
    }

    @media (max-width: 480px) {
      .construction-modal {
        width: 95%;
      }
      
      .construction-modal-header h2 {
        font-size: 1.5rem;
      }
      
      .construction-modal-body p {
        font-size: 1rem;
      }
      
      .construction-modal-button {
        padding: 10px 30px;
        font-size: 1rem;
      }
    }
  </style>
</head>
<body data-step="4">
  <!-- Construction Warning Modal -->
  <div class="construction-modal-overlay" id="constructionModal">
    <div class="construction-modal">
      <div class="construction-modal-header">
        <h2>⚠️ Under Construction</h2>
      </div>
      <div class="construction-modal-body">
        <p>This page is still under construction and the features you see have yet to be complete.</p>
        <p>We're actively working on bringing you the best experience possible!</p>
      </div>
      <div class="construction-modal-footer">
        <button class="construction-modal-button" onclick="closeConstructionModal()">Got it!</button>
      </div>
    </div>
  </div>

  <div class="setup-shell">
    <div class="header"><div class="logo">SkillSwap</div></div>
    <h1 class="step-title">Creating your Account/Editing Account</h1>
    <p class="subtitle">Choose your Colors</p>

    <div class="form-stack">
      <div class="input-card">
        <div class="colors-row" id="colorRow">
          <button class="swatch" data-color="#0ea5e9" style="background:#0ea5e9"></button>
          <button class="swatch" data-color="#ef4444" style="background:#ef4444"></button>
          <button class="swatch" data-color="#22c55e" style="background:#22c55e"></button>
          <button class="swatch" data-color="#06b6d4" style="background:#06b6d4"></button>
          <button class="swatch" data-color="#eab308" style="background:#eab308"></button>
          <button class="swatch" data-color="#7c3aed" style="background:#7c3aed"></button>
        </div>
      </div>

      <div class="input-card">
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
      </div>
    </div>

    <div class="nav-bar">
      <a class="btn btn-primary" href="page3.php">Back</a>
      <div class="spacer"></div>
      <button id="finish" class="btn btn-primary" type="button">Finish</button>
    </div>

    <div class="progress" aria-label="Progress">
      <span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot active"></span>
    </div>
  </div>
  <script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
  <script>
    // Show construction modal on page load
    window.addEventListener('load', function() {
      document.getElementById('constructionModal').style.display = 'flex';
    });

    // Close construction modal function
    function closeConstructionModal() {
      document.getElementById('constructionModal').style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('constructionModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeConstructionModal();
      }
    });
  </script>
</body>
</html>
