<?php
require 'settings/conn.php';
require 'func.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pokemonId = $_POST['pokemonId'];
    $expgained = $_POST['exp'];
    $expdet = getPokemonExpDetails($pokemonId);
    $maxlevel = 100;

    if ($expdet['success']) {
        while ($expgained && $expdet['level'] < $maxlevel) {
            $expTNL = $expdet['expTNL'];

            if ($expgained >= $expTNL) {
                $querylvl = "UPDATE pokemon SET Level = (Level + 1) WHERE PokemonId = ?";
                $stmtlvl = mysqli_prepare($conn, $querylvl);
                mysqli_stmt_bind_param($stmtlvl, 'i', $pokemonId);
                $resultlvl = mysqli_stmt_execute($stmtlvl);
                mysqli_stmt_close($stmtlvl);
                if (!$resultlvl) {
                    echo json_encode(array('success' => false, 'message' => 'Can\'t level up'));
                }
                $expgained = $expgained - $expTNL;
                $expToAdd = $expTNL;

                fillMonStats($pokemonId);
            } else {
                $expToAdd = $expgained;
                $expgained = 0;
            }
            $queryexp = "UPDATE pokemon SET Exp = Exp + ? WHERE PokemonId = ?";
            $stmtexp = mysqli_prepare($conn, $queryexp);
            mysqli_stmt_bind_param($stmtexp, 'ii', $expToAdd, $pokemonId);
            $resultexp = mysqli_stmt_execute($stmtexp);
            mysqli_stmt_close($stmtexp);
            
            $expdet = getPokemonExpDetails($pokemonId);
        }

        echo json_encode(array('success' => true));
    } else {
        echo "Error: " . $expdet['message'];
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Cant set exp'));
}
