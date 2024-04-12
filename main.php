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

                $.ajax({
                    url: 'addPokemon.php',
                    method: 'POST',
                    data: {
                        pokedexId: pokedexId,
                        level: level
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            getPartyPokemon();
                        } else {
                            alert('Failed to add Pokemon.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error adding Pokemon:', error);
                    }
                });
            });


        });

        function getPartyPokemon() {
            $.ajax({
                url: 'getPartyPokemon.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    displayPokemon(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching Pokemon data:', error);
                }
            });
        }

        function displayPokemon(data) {
            var container = $('#pokemon-container');
            var spritesPath = 'sprites/pkfront/';

            container.empty();

            data.forEach(function(pokemon) {
                var card = $('<div class="pokemon-card"></div>');
                var name = $('<div class="pokemon-name">' + pokemon.Name + '</div>');
                var sprite = $('<div class="pokemon-sprite"><img src="image.php?path=' + spritesPath + pokemon.PokedexId + '.png" alt="sprite" /></div>');
                var types = $('<div class="pokemon-types"></div>');
                var stats = $('<div class="pokemon-stats"></div>');

                if (pokemon.Type1) {
                    types.append('<span class="pokemon-type" style="background-color: #' + pokemon.TypeColor + ';">' + pokemon.Type1 + '</span>');
                }
                if (pokemon.Type2) {
                    types.append('<span class="pokemon-type" style="background-color: #' + pokemon.TypeColor2 + ';">' + pokemon.Type2 + '</span>');
                }

                stats.append('<span class="pokemon-stat">Level: ' + pokemon.Level + '</span>');
                stats.append('<span class="pokemon-stat">HP: ' + pokemon.HP + '</span>');
                stats.append('<span class="pokemon-stat">Attack: ' + pokemon.Attack + '</span>');
                stats.append('<span class="pokemon-stat">Defense: ' + pokemon.Defense + '</span>');
                stats.append('<span class="pokemon-stat">Special Atk: ' + pokemon.SpAtk + '</span>');
                stats.append('<span class="pokemon-stat">Special Def: ' + pokemon.SpDef + '</span>');
                stats.append('<span class="pokemon-stat">Speed: ' + pokemon.Speed + '</span>');
                stats.append('<span class="pokemon-stat">EXP left: ' + (pokemon.ExpTNL ? pokemon.ExpTNL : '0') + '</span>');

                percentage = Math.round(((pokemon.Exp - pokemon.MinExp) / pokemon.ExpTNL) * 100);

                var progressBar = $('<div class="progress-bar"><div class="progress" id="progress' + pokemon.PokemonId + '"></div></div>');

                stats.append(progressBar);
                card.append(name, sprite, types, stats);

                container.append(card);

                $("#progress" + pokemon.PokemonId).css("width", percentage + "%");
            });
        }
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