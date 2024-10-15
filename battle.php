<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MONS</title>
    <script src="jquery-3.7.1.min.js"></script>
    <link rel='stylesheet' href='style.css'>
    <script src="func.js"></script>
    <script src="script_battle.js"></script>

</head>

<body>

    <?php

    session_start();

    require 'settings/conn.php';
    require 'encrypt.php';
    require 'func.php';

    $password = getenv('ENCRYPTION_PASSWORD');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['data'])) {
            $encryptedData = $_POST['data'];

            $decryptedData = decrypt($encryptedData);

            $data = json_decode($decryptedData, true);

            if (!$data) {
                error_log("JSON decode error for data: " . $decryptedData);
                echo "JSON decode error.";
                exit;
            }

            if (isValidToken($data)) {
                $token = getTokenData($data['token']);

                if ($token['userid'] == $_SESSION['userid']) {
                    $userId = $token['userid'];
                    $enemyId = $data['enemyUserId'];
                } else {
                    echo "Data error.";
                    header('Location: index.php');
                }
            } else {
                echo "Invalid token.";
                header('Location: main.php');
            }
        } else {
            echo "No data.";
            header('Location: main.php');
        }
    } else {
        echo "Invalid request method.";
        header('Location: main.php');
    }

    ?>
    <div class="waiting-box">
        Waiting for your opponent
    </div>
    <div id="party-container">
        <div id="my-party">
            <?php
            $partyInfo = getPartyStatus($userId);
            ?>

            <h3><?php echo $partyInfo['login']; ?></h3>

            <div class="pokeballs">
                <?php
                foreach ($partyInfo['statuses'] as $status) {
                    if ($status == 'OK') {
                        echo '<img src="img/pokeball.svg" alt="Alive Pokémon" width="32" height="32" />';
                    } elseif ($status == 'FNT') {
                        echo '<img src="img/pokeball_gray.svg" alt="Fainted Pokémon" width="32" height="32" />';
                    }
                }
                ?>
            </div>
        </div>

        <div id="battleBody">
            <!-- Enemy Pokemon Information -->
            <?php
            $enemyPokemon = getEnemyPokemonData($enemyId); // Fetch enemy Pokemon data
            ?>
            <div class="enemymon">
                <div class="enemymon-sprite">
                    <img src="img/pokemon_sprites/<?php echo strtolower($enemyPokemon['species_name']); ?>.png" alt="<?php echo $enemyPokemon['species_name']; ?>"> <!-- Use species name for sprite -->
                </div>
                <div class="enemymon-name">
                    <?php echo $enemyPokemon['nickname']; ?> <!-- Enemy nickname (or species name if no nickname) -->
                    <div class="enemymon-level">Lv<?php echo $enemyPokemon['level']; ?></div>
                </div>
                <div class="enemymon-info">
                    <div class="hpcount pokemon-stat">
                        <div class="progress-bar">
                            <div class="progress-hp" style="width: <?php echo $enemyPokemon['hp_percentage']; ?>%; background-color: #271;"></div>
                        </div>
                    </div>
                </div>
                <div class="enemymon-status">
                    <div class='status pokemon-stat' style="background-color: ${statusColor};"><?php echo $enemyPokemon['status'];?></div>
                </div>
            </div>

            <?php
            $userPokemon = getUserPokemonData($userId);
            ?>
            <div class="usermon">
                <div class="usermon-sprite">
                    <img src="img/pokemon_sprites/<?php echo strtolower($userPokemon['species_name']); ?>.png" alt="<?php echo $userPokemon['species_name']; ?>">
                </div>
                <div class="usermon-name">
                    <?php echo $userPokemon['nickname']; ?>
                    <div class="usermon-level">Lv<?php echo $userPokemon['level']; ?></div>
                </div>
                <div class="usermon-info">
                    <div class="hpcount pokemon-stat">
                        HP: <?php echo $userPokemon['hp_left']; ?> / <?php echo $userPokemon['hp_total']; ?>
                        <div class="progress-bar">
                            <?php
                            $userHpPercentage = ($userPokemon['hp_left'] / $userPokemon['hp_total']) * 100;
                            ?>
                            <div class="progress-hp" style="width: <?php echo $userHpPercentage; ?>%; background-color: #271;"></div>
                        </div>
                    </div>
                </div>
                <div class="usermon-status">
                    <div class='status pokemon-stat'><?php echo $userPokemon['status'];?></div>
                </div>
                <div class="usermon-exp">
                    <div class="progress-bar">
                        <?php
                        $userExpPercentage = (($userPokemon['total_exp'] - $userPokemon['min_exp']) / $userPokemon['exp_to_next_level']) * 100;
                        ?>
                        <div class="progress-exp" style="width: <?php echo $userExpPercentage; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>


        <div id="enemy-party">
            <?php
            $partyInfo = getPartyStatus($enemyId);
            ?>

            <h3><?php echo $partyInfo['login']; ?></h3>

            <div class="pokeballs">
                <?php
                foreach ($partyInfo['statuses'] as $status) {
                    if ($status == 'OK') {
                        echo '<img src="img/pokeball.svg" alt="Alive Pokémon" width="32" height="32" />';
                    } elseif ($status == 'FNT') {
                        echo '<img src="img/pokeball_gray.svg" alt="Fainted Pokémon" width="32" height="32" />';
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <div id="battle-details-container">
        <div id="moves-container">
            <h3>Moves</h3>
        </div>
        <div id="battle-info-container">
            <h3>Battle Info</h3>
        </div>
    </div>

    <style>
        div {
            outline: 1px solid white;
        }

        #party-container {
            display: flex;
            justify-content: space-between;
            box-sizing: border-box;
            padding: 0;
            align-items: flex-start;
            min-height: 500px;
            margin-top: 2px;
        }

        #my-party,
        #enemy-party {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            max-width: 15%;
            min-width: 10%;
            min-height: 300px;
            text-align: center;
            overflow: hidden;
            padding: 20px;
        }

        .usermon-exp {
            position: absolute;
            height: 20px;
            bottom: 0px;
        }

        .usermon-exp>.progress-bar,
        .progress-exp {
            margin-top: 7px;
            height: 7px;
        }


        .usermon-exp>.progress-bar,
        .hpcount>.progress-bar {
            width: 340px;
        }

        #my-party {
            align-items: flex-start;
        }

        #enemy-party {
            align-items: flex-end;
            text-align: right;
        }

        .pokeballs {
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: flex-start;
            min-height: 242px;
        }

        #battleBody {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 75%;
            min-height: 500px;
            text-align: center;
            position: relative;
        }

        .waiting-box {
            border: 2px solid black;
            background-color: #f0f0f0;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            width: 300px;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            top: 200px;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
        }

        img {
            display: inline-block;
        }

        h3 {
            font-size: 20px;
            margin-bottom: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #battle-details-container {
            display: flex;
            width: 100%;
            box-sizing: border-box;
            padding: 20px;
        }

        #moves-container {
            width: 35%;
            padding: 10px;
            box-sizing: border-box;
            border: 2px solid white;
            overflow-y: auto;
            height: 170px;
        }

        #battle-info-container {
            width: 65%;
            padding: 10px;
            box-sizing: border-box;
            border: 2px solid white;
            overflow-y: auto;
        }

        .enemymon,
        .usermon {
            width: 400px;
            position: absolute;
        }

        .enemymon {
            top: 10;
            right: 30;
            height: 250px;
        }

        .usermon {
            bottom: 10;
            left: 30;
            height: 270px;
        }

        .enemymon-sprite,
        .usermon-sprite {
            width: 200px;
            height: 200px;
            background-color: rgba(0, 0, 0, 0.3);
            background-size: cover;
        }

        .enemymon-info,
        .usermon-info {
            height: 50px;
            background-color: rgba(0, 0, 0, 0.3);
            position: absolute;
            bottom: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .enemymon-info,
        .usermon-name,
        .usermon-status {
            right: 0;
        }

        .enemymon-sprite {
            background-image: url('https://tcrf.net/images/c/cf/GS_990613_pokemon_front_220.png');
            position: absolute;
            top: 0;
            right: 0;
        }

        .usermon-sprite {
            background-image: url('https://tcrf.net/images/5/5c/GS_990613_pokemon_back_220.png');
            position: absolute;
            top: 0;
            left: 0;
        }

        .enemymon-name,
        .usermon-name {
            width: 200px;
            height: 50px;
            bottom: 50px;
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .enemymon-name {
            left: 0;
        }

        .enemymon-level,
        .usermon-level {
            margin: 10px;
        }

        .enemymon-status,
        .usermon-status {
            width: 60px;
            height: 50px;
            bottom: 0;
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .usermon-status,
        .usermon-info {
            bottom: 20px;
        }

        .usermon-status {
            bottom: 10px;
        }

        .usermon-name {
            bottom: 70px;
        }

        .status {
            margin-top: 15px;

        }
    </style>


    <script>
        $(document).ready(async function() {

        })
    </script>

</body>