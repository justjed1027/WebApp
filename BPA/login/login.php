<?php
    session_start();
    require_once '../database/User.php';
    
    $username = ""; 
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

        if(empty($_POST['username'])){
            $uerror = true;
        }else{
            $username = sanitize_input($_POST['username']);
        }

        if(empty($_POST['password'])){
            $perror = true;
        }else{
            $password = sanitize_input($_POST['password']);
        }

        if(!$uerror && !$perror){
            //No errors. Attempt to authenticate user. 

            $userid = User::validateUser($username, $password);

            if($userid != 0){
                $_SESSION['user_id'] = $userid;
                header("Location: ../post/post.php");
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
<title>SkillShare - Log In</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="login.css">
</head>
<body>
<div class="container">
<h1>SkillShare</h1>
<p class="sub">Log in to your account <br> Don’t have an account? <a href="../signup/signup.php">Sign up</a></p>

<?php if($invalid_login == true) {
    echo "<font color=red>&middot; Couldn't log you in! Please try again.</font><br><br>"; 
}
?>
<form id="loginForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
<label for="email">Username</label>
<input type="text" id="email" name="username" value="<?=$username?>" placeholder="skilldusr1" required>


<label for="password">Password</label>
<input type="password" id="password" name="password" placeholder="*******" required>


<div class="options">
<div>
<input type="checkbox" id="remember">
<label for="remember">Remember me</label>
</div>
<a href="#" class="forgot">Forgot your password?</a>
</div>


<button type="submit">Log in</button>
</form>


<div class="divider">Or continue with</div>
<div class="socials">
<button class="social-btn github">GitHub</button>
<button class="social-btn linkedin">LinkedIn</button>
</div>
</div>

</body>
</html>