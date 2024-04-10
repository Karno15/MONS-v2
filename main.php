<?php
require 'settings/conn.php';
require 'func.php';
session_start();
?>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MONS</title>
    <script src="jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#editbutton").click(function() {
                $('#editbox').show();
            })
            $("#closeedit").click(function() {
                $('#editbox').hide();
            })

            $('#fileInput').on('change', function() {
                var fileName = $(this).val().split('\\').pop(); // Get the file name
                $('#file').text(fileName); // Update the content of the file div
            });

        });
    </script>
    <link rel='stylesheet' href='style.css'>
</head>

<body>
    <?php

    ?>
    <div id="container">
        <button id="editbutton">EDIT PROFILE</button>
        <div id="editbox">
            <button id="closeedit">X</button>
            <form action="editprofile.php" method="POST" enctype="multipart/form-data">
                <textarea name="signature" class="edit" ><?php echo getSignature($_SESSION['userid']); ?></textarea>
                <div id="upload">
                    <label for="fileInput" class="upload">Choose File</label>
                </div>
                <input type="file" name="fileInput" id="fileInput" style="display: none;">
                <div id="file"></div>
                <button type="submit">SAVE</button>
            </form>
        </div>
        <br>
        <?php
        echo '<img src="image.php?path=' . getAvatarPath($_SESSION['userid']) . '" alt="avatar" id="avatar" />';

        $result = callProc('showPartyData', array(
            array('value' => $_SESSION["userid"], 'type' => 'i')
        ));

        print_r($result);
        ?>
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