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

                    echo "Rozpoczynanie walki między użytkownikiem $userId a przeciwnikiem $enemyId.";
                } else {
                    echo "Data error.";
                    header('Location: index.php');
                }
            } else {
                echo "Invalid token.";
            }
        } else {
            echo "No data.";
        }
    } else {
        echo "Invalid request method.";
    }

    ?>
    <div id="my-party"></div>
    <div id="enemy-party"></div>
    <script>
        $(document).ready(async function() {

            const enemyId = "<?php echo $enemyId; ?>";
            getPartyPokemon('', 1, function(response) {
                var container = $('#my-party');
                displayMon(response, container);

                console.log(response);
            });

            getPartyPokemon(enemyId, 1, function(response) {
                var container = $('#enemy-party');
                displayMon(response, container);

                console.log(response);
            });

        })
    </script>

</body>