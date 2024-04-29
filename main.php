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
        $(document).ready(function() {
            var token = getCookie('token');

            $("#editbutton").click(function() {
                $('#editbox').show();
            })

            $("#closeedit").click(function() {
                $('#editbox').hide();
            })

            $('#fileInput').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $('#file').text(fileName);
            });

            getPartyPokemon();
            getBoxPokemon();

            const socket = new WebSocket('ws://localhost:8080');

            socket.addEventListener('open', function(event) {
                console.log('Connected to the server');
            });

            socket.addEventListener('close', function(event) {
                console.log('Disconnected from the server');
            });

            $('#addExp').on('click', function() {
                const pokemonId = $('#addexp-pokemonId').val();
                const exp = $('#addexp-exp').val();
                if (pokemonId && exp) {
                    const data = {
                        type: 'grant_exp',
                        pokemonId: pokemonId,
                        exp: exp,
                        token: token
                    };
                    socket.send(JSON.stringify(data));
                    setTimeout(function() {
                        getPartyPokemon();
                        getBoxPokemon();
                    }, 200);
                } else {
                    alert('Please enter Pokemon ID and Exp');
                }
            });

            $('#addPokemon').click(function() {
                const pokedexId = $('#addPokemon-PokedexId').val();
                const level = $('#addPokemon-level').val();

                if (pokedexId && level) {
                    const data = {
                        type: 'add_mon',
                        pokedexId: pokedexId,
                        level: level,
                        token: token
                    };
                    socket.send(JSON.stringify(data));
                    getPartyPokemon();
                    getBoxPokemon();
                } else {
                    alert('Please enter pokedex ID and level');
                }
            });

            $(document).on('click', '.release-btn', function() {
                var pokemonId = $(this).data('pokemon-id');
                var confirmRelease = confirm("Are you sure you want to release this Pokémon?");
                if (confirmRelease) {
                    var data = {
                        type: 'release_pokemon',
                        pokemonId: pokemonId,
                        token: token
                    };
                    socket.send(JSON.stringify(data));
                    getPartyPokemon();
                    getBoxPokemon();
                }
            });
        });
    </script>
</head>

<body>
    <div class="infobox">
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