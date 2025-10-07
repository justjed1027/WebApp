<?php
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';

$user_username ="";
$user_password ="";
$user_email ="";
$user_is_admin = 0;
$errors = [];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    //Get form data
    $user_username = trim($_POST['fullName']);
    $user_password = trim($_POST['password']);
    $user_email = trim($_POST['email']);
    $user_is_admin = 0; //Default to non-admin for now

    //Basic validation
    if(empty($user_username)){
        $errors[] = "Username is required.";
    }
    if(empty($user_password)){
        $errors[] = "Password is required.";
    }
    if(empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)){
        $errors[] = "A valid email is required.";
    }

    //If no errors, proceed to create user
    if(empty($errors)){
        $newUser = new User();
        $newUser->username = $user_username;
        $newUser->password = $user_password;
        $newUser->email = $user_email;
        $newUser->is_admin = $user_is_admin;

        //Insert user into database
        $newUser->insert();

        if($newUser->id > 0){
            //Success - redirect to login or dashboard
            header("Location: ../login/login.php");
            exit();
        } else {
            $errors[] = "Error creating account. Please try again.";
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


<form id="signupForm" action="signup.php" method="POST"> 
<label for="fullName">User Name</label>
<input type="text" id="fullName" name="fullName" required>


<label for="email">Email address</label>
<input type="email" id="email" name="email" required>


<label for="password">Password</label>
<input type="password" id="password" name="password" required>


<label for="accountType">Account Type</label>
<select id="accountType" name="accountType">
<option value="student">Student</option>
<option value="teacher">Teacher</option>
<option value="professional">Professional</option>
</select>


<div class="checkbox">
<input type="checkbox" id="terms" required>
<label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
</div>


<button type="submit">Sign up</button>
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