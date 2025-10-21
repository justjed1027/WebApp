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
        $newUser->insert();

        if($newUser->user_id > 0){
            //Success - redirect to login or dashboard
            $_SESSION['user_id'] = $newUser->user_id;
            header("Location: ../post/post.php");
            exit;
        } else {
            $errors['general'] = "Error creating account. Please try again.";
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
<input type="password" id="password" name="password" required>
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

</body>
</html>