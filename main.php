<?php

session_start();

require 'settings/conn.php';
require 'func.php';

$token = generateToken($_SESSION["userid"], $_SESSION["login"], $_SESSION["uid"]);

setcookie("token", $token, time() + (86400 * 30), "/");

if (!isset($_SESSION["userid"], $_SESSION["login"], $_SESSION["uid"])) {
    echo 'no session';
    header('Location: index.php');
}
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
    <div id="loading-overlay" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
        <div class="loading-info">Loading...</div>
    </div>

    <div id="modal" class="modal">
        <div class="modal-content">
            <span id="modal-message"></span><br />
            <button class='popup-buttons' id="modal-confirm-button">OK</button>
        </div>
    </div>

    <div id="confirm-modal" class="modal">
        <div class="modal-content">
            <span id="confirm-message"></span><br />
            <button class='popup-buttons' id="confirm-yes-button">Yes</button>
            <button class='popup-buttons' id="confirm-no-button">No</button>
        </div>
    </div>

    <div id="prompt-modal" class="modal">
        <div class="modal-content">
            <span id="prompt-message"></span>
            <input type="text" id="prompt-input" /><br />
            <button class='popup-buttons' id="prompt-confirm-button">OK</button>
            <button class='popup-buttons' id="prompt-cancel-button">CANCEL</button>
        </div>
    </div>

    <div id="option-modal" class="modal">
        <div class="modal-content">
            <span id="option-message"></span><br />
            <div id="option-list"></div>
            <button class='popup-buttons' id="option-confirm-button">OK</button>
            <button class='popup-buttons' id="option-cancel-button">CANCEL</button>
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
            PokemonID1:<input type="number" id="battle-pokemonIdA">
            <br>
            PokemonID2:<input type="number" id="battle-pokemonIdB">
            <br>
            <button id="battle">Battle</button>
        </p>


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