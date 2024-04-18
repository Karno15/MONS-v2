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
    <link rel='stylesheet' href='style.css'>
    <script src="script.js"></script>
    <script>
        $(document).ready(function() {
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

            $('#addPokemon').click(function() {
                var pokedexId = $('#addPokemon-PokedexId').val();
                var level = $('#addPokemon-level').val();
                addPokemon(pokedexId, level);
            });


            $('#addExp').click(function() {
                var pokemonId = $('#addexp-pokemonId').val();
                var exp = $('#addexp-exp').val();
                addExp(pokemonId, exp);
            });
        });
    </script>
</head>

<body>
    <div id="container">
        <button id="editbutton">EDIT PROFILE</button>
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
        <div id="test"></div>
    </div>
</body>

</html>