<?php

session_start();

require 'settings/conn.php';
require 'func.php';

$token = generateToken($_SESSION["userid"], $_SESSION["login"], $_SESSION["uid"]);

setcookie("token", $token, time() + (86400 * 30), "/");

?>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MONS</title>
    <script src="jquery-3.7.1.min.js"></script>
    <link rel='stylesheet' href='style.css'>
    <script src="script.js"></script>
    <script>
        $(document).ready(async function() {
            await getPartyPokemon();
            await getBoxPokemon();
        });
    </script>
</head>

<body>
    <div id="modal" class="modal">
        <div class="modal-content">
            <span id="modal-message"></span><br/>
            <button id="modal-confirm-button">OK</button>
        </div>
    </div>

    <div id="confirm-modal" class="modal">
        <div class="modal-content">
            <span id="confirm-message"></span><br/>
            <button id="confirm-yes-button">Yes</button>
            <button id="confirm-no-button">No</button>
        </div>
    </div>

    <div id="prompt-modal" class="modal">
        <div class="modal-content">
            <span id="prompt-message"></span>
            <input type="text" id="prompt-input" />
            <button id="prompt-ok-button">OK</button>
        </div>
    </div>

    <div id="container">
        <button id="editbutton">EDIT PROFILE</button><a href="logout.php">LOGOUT</a>
        <div id="editbox">
            <button id="closeedit">X</button>
            <form action="editprofile.php" method="POST" enctype="multipart/form-data">
                <textarea name="signature" class="edit"><?php echo getSignature($_SESSION['userid']); ?></textarea>
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
        echo '<img src="image.php?path=' . getAvatarPath($_SESSION['userid']) . '" alt="avatar" id="avatar" /><br>';

        echo '<b>' . $_SESSION["login"] . '</b>';
        ?>
        <p>
            Pokedex ID:<input type="number" id="addPokemon-PokedexId">
            <br>
            Level:<input type="number" id="addPokemon-level">
            <br>
            <button id="addPokemon">Add Pokemon</button>
        </p>
        <div id="pokemon-container"></div>
        <p>
            PokemonID:<input type="number" id="addexp-pokemonId">
            <br>
            Exp:<input type="number" id="addexp-exp">
            <br>
            <button id="addExp">Add Exp</button>
        </p>
        <hr>
        <p>Box Pokemon</p>
        <div id="box-container"></div>
        <div id="test"></div>
    </div>
</body>




</html>