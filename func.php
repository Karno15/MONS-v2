<?php

function addMessage($message) {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }

    array_unshift($_SESSION['messages'], $message);

    $maxMessages = 5;
    if (count($_SESSION['messages']) > $maxMessages) {
        array_pop($_SESSION['messages']);
    }
}

function getAvatarPath($userId)
{
    global $conn;

    $query = "SELECT avatar FROM users WHERE userid = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $avatarPath);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $avatarPath;
}
function getSignature($userId)
{
    global $conn;

    $query = "SELECT signature FROM users WHERE userid = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $signature);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $signature;
}

function isPartyFull()
{
    global $conn;

    $query = "CALL showPartyData(?)";
    $stmt = mysqli_prepare($conn, $query);
    $userId = $_SESSION["userid"];
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    if (count($rows) < 6) {
        return true;
    } else {
        return false;
    }
}

function fillMonStats($pokemonId)
{
    global $conn;

    $HP = getCurrentHP($pokemonId);
    $oldHP = $HP['HP'];
    $oldHPLeft = $HP['HPLeft'];

    $query = "CALL fillMonStats(?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
    $success = mysqli_stmt_execute($stmt);

    $newHP = getCurrentHP($pokemonId);

    $HPtoSet = $oldHPLeft + ($newHP['HP'] - $oldHP);

    $query = "UPDATE pokemon SET HPLeft = ? where PokemonId= ? ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $HPtoSet, $pokemonId);
    $success = mysqli_stmt_execute($stmt);

    if ($oldHPLeft <= 0) {
        $queryupdate = "UPDATE pokemon SET Status = 'OK' where PokemonId= ? ";
        $stmtupdate = mysqli_prepare($conn, $queryupdate);
        mysqli_stmt_bind_param($stmtupdate, 'i', $pokemonId);
        $successupdate = mysqli_stmt_execute($stmtupdate);
    }

    if (!$success) {
        echo "Error executing stored procedure: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}


function getPokemonExpDetails($pokemonId)
{
    global $conn;

    $query = 'SELECT p.Level, p.Exp, (e.Exp + e.ExpTNL - p.Exp) AS "ExpTNL"
              FROM pokemon p
              JOIN pokedex pk ON pk.PokedexId = p.PokedexId
              JOIN exptype e ON e.Level = p.Level AND pk.ExpType = e.Type
              WHERE pokemonId = ?';

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        return array(
            'success' => true,
            'exp' => $row['Exp'],
            'level' => $row['Level'],
            'expTNL' => $row['ExpTNL']
        );
    } else {
        return array(
            'success' => false,
            'message' => 'Can\'t retrieve Pokemon exp details'
        );
    }
}

function canEvolve($pokemonId)
{
    global $conn;

    $query = 'SELECT e.PokedexId,e.Name, e.LevelReq,e.NameNew,e.PokedexIdNew 
              FROM `evos` e JOIN pokemon p ON p.PokedexId=e.PokedexId
              WHERE EvoType="EXP" AND PokemonId= ? ';

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return $rows[0];
    } else {
        return false;
    }
}

function fillMonMoves($pokemonId) 
{
    global $conn;

    $query = 'SELECT e.PokedexId,e.Name, e.LevelReq,e.NameNew,e.PokedexIdNew 
              FROM `evos` e JOIN pokemon p ON p.PokedexId=e.PokedexId
              WHERE EvoType="EXP" AND PokemonId= ? ';

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

}

function getCurrentHP($pokemonId)
{
    global $conn;

    $query = "SELECT HP, HPLeft FROM pokemon WHERE PokemonId = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $pokemonId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $HP, $HPLeft);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return array(
        'HP' => $HP,
        'HPLeft' => $HPLeft
    );
}

function playerDefeatedOpponent()
{
    if (isset($_SESSION['addexp']) && $_SESSION['addexp']) {
        return true;
    } else {
        return false;
    }
}
