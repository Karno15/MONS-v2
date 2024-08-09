<?php

session_start();

ob_start();

require 'encrypt.php';
require 'func.php';

$password = getenv('ENCRYPTION_PASSWORD');

if (isset($_GET['data'])) {
    // Decode the URL-encoded data
    $encryptedData = urldecode($_GET['data']);

    // Decrypt the data
    $decryptedData = decrypt($encryptedData);
    // Handle potential decryption errors
    if ($decryptedData === false) {
        echo "Decryption failed.";
        exit;
    }

    $data = json_decode($decryptedData, true);

    if ($data === null) {
        echo "JSON decode error.";
        exit;
    }

    // Proceed with token validation and other checks
    if (isValidToken($data)) {
        $token = getTokenData($data['token']);

        if ($token['userid'] == $_SESSION['userid']) {
            $userId = $token['userid'];
            $enemyId = $data['enemyUserId'];

            echo "Rozpoczynanie walki między użytkownikiem $userId a przeciwnikiem $enemyId.";
            ob_end_flush();
        } else {
            echo "Data error.";
            ob_end_flush();
        }
    } else {
        echo "Invalid token.";
        ob_end_flush();
    }
} else {
    echo "No data.";
    ob_end_flush();
}

?>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MONS</title>
    <script src="jquery-3.7.1.min.js"></script>
    <link rel='stylesheet' href='style.css'>
    <script src="script.js"></script>
    
</head>
