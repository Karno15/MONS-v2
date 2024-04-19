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
        while ($expgained > 0 && $expdet['level'] < $maxlevel) {
            $expTNL = $expdet['expTNL'];
            $level = $expdet['level'];
            if ($expgained >= $expTNL) {
                $querylvl = "UPDATE pokemon SET Level = (Level + 1) WHERE PokemonId = ?";
                $stmtlvl = mysqli_prepare($conn, $querylvl);
                mysqli_stmt_bind_param($stmtlvl, 'i', $pokemonId);
                $resultlvl = mysqli_stmt_execute($stmtlvl);
                mysqli_stmt_close($stmtlvl);
                if (!$resultlvl) {
                    echo json_encode(array('success' => false, 'message' => 'Can\'t level up'));
                }
                $expgained -= $expTNL;
                if ($expdet['level'] == $maxlevel - 1) {
                    $expToAdd = 0;
                } else {
                    $expToAdd = min($expTNL, $expgained);
                }
                $level++;
                $evo = canEvolve($pokemonId);

                if ($evo && $evo['LevelReq'] <= $level) {
                    $queryevo = "UPDATE pokemon SET PokedexId= ? WHERE PokemonId = ?";
                    $stmtevo = mysqli_prepare($conn, $queryevo);
                    mysqli_stmt_bind_param($stmtevo, 'ii', $evo['PokedexIdNew'], $pokemonId);
                    $resultevo = mysqli_stmt_execute($stmtevo);
                    mysqli_stmt_close($stmtevo);
                    if (!$resultevo) {
                        echo json_encode(array('success' => false, 'message' => 'Can\'t evolve'));
                    }

                    $evoInfo = $evo['Name'] . ' evolved into ' . $evo['NameNew'];
                }

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
