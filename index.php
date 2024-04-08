<?php

session_start();

if (isset($_SESSION['uid'])) {
    require_once 'settings/conn.php';

    if (uidExists($_SESSION['uid'])) {
        header("Location: main.php");
        exit;
    } else {
        echo 'error.';
    }
}

ob_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MONS</title>
    <script src="jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#register").click(function() {
                $('#popup').show();
            })
            $("#closepopup").click(function() {
                $('#popup').hide();
            })
        });
    </script>
    <link rel='stylesheet' href='style.css'>
</head>

<body>
    <div id="popup">
        <button id="closepopup">X</button>
        <form action='register.php' method='post'>
            <input type="text" name="login" class="input-login">
            <input type="password" name="password" class="input-login">
            <button type='submit' id="login">REGISTER</button>
        </form>
    </div>
    <?php

    echo "hello mons!<br>";

    ?>
    <div id="container">
        <div id="login">
            <form action='login.php' method='post'>
                <input type="text" name="login" class="input-login">
                <input type="password" name="password" class="input-login">
                <button type='submit' id="login">LOGIN</button>
            </form>
            <button id="register">REGISTER</button>
        </div>
    </div>

    <div id='footer'>v<span id='ver'>
            <?php
            echo file_get_contents('verinfo.txt');
            ob_flush();
            ?>
        </span> Made by @karkarno
    </div>
</body>

</html>