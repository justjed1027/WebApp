<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';

$user_username ="";
$user_password ="";
$user_email ="";
$account_type = 0; 
$errors = [];

function sanitize_input($data){
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data); 
    return $data;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    //Get form data
    if(!empty($_POST['fullName'])){
        $user_username = sanitize_input($_POST['fullName']);
    }else{
        $errors['fullName'] = "Username is required.";
    }
    
    $user_password = sanitize_input($_POST['password']);
    $user_email = sanitize_input($_POST['email']);
    $user_is_admin = 0; //Default to non-admin for now


    if(empty($user_password)){
        $errors['password'] = "Password is required.";
    }
    if(empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)){
        $errors['email'] = "A valid email is required.";
    }

    //If no errors, proceed to create user
    if(empty($errors)){
        $newUser = new User();
        $newUser->user_username = $user_username;
        $newUser->user_password = $user_password;
        $newUser->user_email = $user_email;
        $newUser->user_is_admin = 0; 

        //Insert user into database
        $result = $newUser->insert();

        if($result['success']){
            //Success - redirect to login or dashboard
            $_SESSION['user_id'] = $newUser->user_id;
            header("Location: ../post/post.php");
            exit;
        } else {
            $errors['general'] = $result['message'];
        }
    }
} 


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkillShare - Create Account</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="signup.css">
</head>
<body>
<div class="container">
<h1>SkillShare</h1>
<p class="sub">Create your account <br> Already have an account? <a href="../login/login.php">Log in</a></p>

<form id="signupForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">


<label for="fullName">User Name</label>
<input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($user_username); ?>" required>
<?php if (!empty($errors['fullName'])): ?>
    <p class="error"><?php echo $errors['fullName']; ?></p>
<?php endif; ?>

<label for="email">Email address</label>
<input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
<?php if (!empty($errors['email'])): ?>
    <p class="error"><?php echo $errors['email']; ?></p>
<?php endif; ?>

<label for="password">Password</label>
<div class="passwd-wrap">
<input type="password" id="password" name="password" required>
<button type="button" onclick="togglePassword()"> 

<svg id="eye-slash" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16">
  <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/>
  <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/>
  <path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>
</svg>


    <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye hidden" viewBox="0 0 16 16">
  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
</svg>
</button>
</div>
<?php if (!empty($errors['password'])): ?>
    <p class="error"><?php echo $errors['password']; ?></p>
<?php endif; ?>

<label for="accountType">Account Type</label>
<select id="accountType" name="accountType">
    <option value="0">Student</option>
</select>

<button type="submit">Sign up</button>

<?php if (!empty($errors['general'])): ?>
    <p class="error"><?php echo $errors['general']; ?></p>
<?php endif; ?>

</form>


<div class="divider">Or continue with</div>
<div class="socials">
<button class="social-btn github">GitHub</button>
<button class="social-btn linkedin">LinkedIn</button>
</div>
</div>
<script src="signup.js"></script>
</body>
</html>