<?php
    session_start();
    require_once '../database/User.php';
    
    $user = new User(); 

    //If userid exists in $_SESSION, then account is being updated. 
    //Otherwise, a new account is being created. 
    //We will use this page to insert and update user accounts. 
    if(!empty($_SESSION['user_id'])){

        $user->populate($_SESSION['user_id']);  
    }else{
        header ('location: ../landing/landing.php');
    }

    if($_SERVER["REQUEST_METHOD"] == "GET") {

        if(!empty($_GET['action']) && $_GET['action'] == 'logout'){

            $_SESSION = [];
            session_destroy();
            setcookie("PHPSESSID", "", time()-3600, "/");
            header('location: ../landing/landing.php');
        }
    }



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    Welcome back, <?=$user->user_username?>
    <br><br>
    <a href="post.php?action=logout">Sign Out</a>
</body>
</html>