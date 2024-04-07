<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MONS</title>
</head>

<body>

    <?php

    echo "hello mons!<br>";

    ?>

    <img src="image.php?path=avatars/kar.png" alt="Image" />


    <div id='footer'>v<span id='ver'>
            <?php
            echo file_get_contents('verinfo.txt');
            ob_flush();
            ?>
        </span> Made by @karkarno
    </div>
</body>

</html>