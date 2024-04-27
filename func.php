<?php

function generateToken($userId, $login, $uid)
{
    $creationDate = date('Y-m-d H:i:s');
    $tokenData = array(
        'userid' => $userId,
        'login' => $login,
        'uid' => $uid,
        'created' => $creationDate
    );
    $token = base64_encode(json_encode($tokenData));
    return $token;
}

function isValidToken($data)
{
    if (!isset($data['token'])) {
        return false;
    }

    $token = $data['token'];
    $tokenData = getTokenData($token);

    if (!isset($tokenData['created'])) {
        return false;
    }

    $creationDate = strtotime($tokenData['created']);

    if (time() - $creationDate <= 1800) {
        return true;
    } else {
        return false;
    }
}

function getTokenData($token)
{
    $decodedToken = base64_decode($token);
    $tokenData = json_decode($decodedToken, true);

    return $tokenData;
}

function logClientMessage($clientId, $message)
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp][INFO]Client $clientId: $message" . PHP_EOL;
    //to uncomment after releasing

    //file_put_contents('../logs/logs.log', $logEntry, FILE_APPEND);
}

function logServerMessage($message, $label = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp][$label]Server: $message" . PHP_EOL;
    //to uncomment after releasing

    //file_put_contents('../logs/logs.log', $logEntry, FILE_APPEND);
}


function addMessage($message)
{
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

function belongsToUser($pokemonId, $token)
{
    global $conn;

    $tokenData = getTokenData($token);
    $userId = $tokenData['userid'];

    $stmt = $conn->prepare("SELECT UserId FROM pokemon WHERE PokemonId = ?");
    $stmt->bind_param("i", $pokemonId);
    $stmt->execute();
    $stmt->bind_result($ownerUserId);
    $stmt->fetch();
    $stmt->close();

    return $ownerUserId == $userId;
}



function isPartyFull($userId)
{
    global $conn;

    $query = "CALL showPartyData(?)";
    $stmt = mysqli_prepare($conn, $query);
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

function addExp($pokemonId, $expgained, $token)
{
    global $conn;

    if (!belongsToUser($pokemonId, $token)) {
        echo json_encode(array('success' => false, 'message' => 'Pokemon does not belogns to user!')) . "\n";
        return false;
    } else {
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
                        echo json_encode(array('success' => false, 'message' => 'Can\'t level up' . "\n"));
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

                        $evoInfo = $evo['Name'] . '(' . $pokemonId . ')' . ' evolved into ' . $evo['NameNew'];
                        echo $evoInfo;
                        logServerMessage($evoInfo);
                        addMessage($evoInfo);
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

            echo json_encode(array('success' => true)) . "\n";
        } else {
            echo json_encode(array('success' => false, 'message' => $expdet['message'])) . "\n";
        }
    }
}

function addMon($pokedexId, $level, $token)
{
    global $conn;

    $tokenData = getTokenData($token);
    $userId = $tokenData['userid'];
    $inparty = isPartyFull($userId);

    $query = "INSERT INTO pokemon (`UserId`, `PokedexId`, `Level`, `Status`, `ItemHeld`, `inParty`, `Released`) 
          VALUES (?, ?, ?, 'OK', 0, ?, 0)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'iiii', $userId, $pokedexId, $level, $inparty);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $lastInsertId = mysqli_insert_id($conn);

    $currentHP = getCurrentHP($lastInsertId);

    if ($result && $lastInsertId) {
        fillMonStats($lastInsertId);
    }

    if ($result) {
        echo json_encode(array('success' => true)) . "\n";
    } else {
        echo json_encode(array('success' => false)) . "\n";
    }

    if ($inparty) {
        addMessage('Pokemon joined the party!');
    } else {
        addMessage('Team is full! Pokemon was put into the box!');
    }
}

function releasePokemon($pokemonId, $token)
{
    global $conn;

    if (!belongsToUser($pokemonId, $token)) {
        echo json_encode(array('success' => false, 'message' => 'Pokemon does not belogns to user!')) . "\n";
        return false;
    } else {
        $query = "UPDATE pokemon SET Released = 1 WHERE PokemonId = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(array('success' => true)) . "\n";
        logServerMessage("Released Pokémon with ID: $pokemonId", 'INFO');
        return $result;
    }
}
