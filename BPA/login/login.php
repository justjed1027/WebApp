<?php
    session_start();
    require_once '../database/User.php';
    
    $email = ""; 
    $password = ""; 
    
    $uerror = false;
    $perror = false; 
    $invalid_login = false;

    function sanitize_input($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data); 
        return $data;
    }

    if($_SERVER['REQUEST_METHOD'] == "POST"){

        if(empty($_POST['email'])){
            $uerror = true;
        }else{
            $email = sanitize_input($_POST['email']);
        }

        if(empty($_POST['password'])){
            $perror = true;
        }else{
            $password = sanitize_input($_POST['password']);
        }

        if(!$uerror && !$perror){
            //No errors. Attempt to authenticate user. 
            // Single source of truth for the admin account email
            // Update this value to the exact email for Admin1
            $ADMIN_EMAIL = 'admin@gmail.com';

            $userid = User::validateUser($email, $password);

            if($userid != 0){
                $_SESSION['user_id'] = $userid;

                // Populate user to read admin flag and email
                $user = new User();
                $user->populate($userid);

                // If this login email is the admin email, ensure admin flag
                if (strtolower($email) === strtolower($ADMIN_EMAIL)) {
                    $_SESSION['is_admin'] = 1;
                    // If DB flag is not set yet, set it now
                    if ((int)$user->user_is_admin !== 1) {
                        require_once '../database/DatabaseConnection.php';
                        $db = new DatabaseConnection();
                        $stmt = $db->connection->prepare("UPDATE user SET user_is_admin = 1 WHERE user_id = ?");
                        if ($stmt) {
                            $stmt->bind_param("i", $userid);
                            $stmt->execute();
                            $stmt->close();
                        }
                        $db->closeConnection();
                    }
                } else {
                    // Otherwise rely on DB flag
                    $_SESSION['is_admin'] = (int)$user->user_is_admin === 1 ? 1 : 0;
                }

                // Redirect based on admin status
                if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1) {
                    header("Location: ../post/post.php?admin=1");
                } else {
                    header("Location: ../post/post.php");
                }
            }else{
                $invalid_login = true;
            }
        }
    }


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkillSwap - Log In</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="login.css">
</head>
<body>
<div class="split-container">
    <!-- Left Panel - Dark with Welcome Message -->
    <div class="left-panel">
        <div class="welcome-content">
<div class="brand-logo">SkillShare</div>
<h1>Welcome back!</h1>
<p class="subtitle">Sign in to learn, collaborate, and share</p>

<?php if($invalid_login == true) {
    echo "<div class='error-message'>Couldn't log you in! Please try again.</div>"; 
}
?>
<?php if(isset($_SESSION['login_error'])) {
    echo "<div class='error-message'>" . htmlspecialchars($_SESSION['login_error']) . "</div>"; 
    unset($_SESSION['login_error']);
}
?>
<div class="auth-buttons">
    <button class="auth-btn google-btn" onclick="googleSignIn()">
        <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Sign in with Google
    </button>
</div>
<form id="emailLoginForm" class="email-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
<label for="email">EMAIL</label>
<input type="text" id="email" name="email" value="<?=$email?>" placeholder="hello@reallygreatsite.com" required>

<label for="password">PASSWORD</label>
<div class="passwd-wrap">
    <input type="password" id="password" name="password" placeholder="******" required>
    <button type="button" onclick="togglePassword()">
        <svg id="eye-slash" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16">
            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/>
            <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/>
            <path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c-.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>
        </svg>
        <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye hidden" viewBox="0 0 16 16">
            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
        </svg>
    </button>
</div>

<p class="forgot-password"><a href="../forgot/forgot.php">Forgot password?</a></p>

<button type="submit" class="signin-btn">SIGN IN</button>

<p class="create-account">Don't have an account? <a href="../signup/signup.php">create account</a></p>
</form>
        </div>
    </div>

    <!-- Right Panel intentionally left blank for future background image -->
    <div class="right-panel"></div>
</div>

<script src="login.js"></script>

</body>
</html>