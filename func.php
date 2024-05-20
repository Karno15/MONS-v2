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

    if (!$success) {
        echo "Error executing stored procedure: " . mysqli_error($conn);
        return;
    }

    mysqli_stmt_close($stmt);

    while (mysqli_more_results($conn)) {
        mysqli_next_result($conn);
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }

    $HP = getCurrentHP($pokemonId);
    $newHP = $HP['HP'];

    $newHPLeft = $oldHPLeft + ($newHP - $oldHP);

    $query = "UPDATE pokemon SET HPLeft = ? WHERE PokemonId = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $newHPLeft, $pokemonId);
    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        echo "Error updating HPLeft: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);

    if ($oldHPLeft <= 0) {
        $queryupdate = "UPDATE pokemon SET Status = 'OK' WHERE PokemonId = ?";
        $stmtupdate = mysqli_prepare($conn, $queryupdate);
        mysqli_stmt_bind_param($stmtupdate, 'i', $pokemonId);
        $successupdate = mysqli_stmt_execute($stmtupdate);

        if (!$successupdate) {
            echo "Error updating status: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmtupdate);
    }
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
    WHERE EvoType="EXP" AND p.Level>=e.LevelReq AND p.PokemonId= ? ';

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return 1;
    } else {
        return 0;
    }
}


function evolvePokemon($pokemonId, $evoType, $token)
{
    global $conn;

    if (!belongsToUser($pokemonId, $token)) {
        $response = array('success' => false, 'message' => 'Pokemon does not belong to user!');
        return $response;
    } else {
        $queryevo = "UPDATE pokemon p JOIN evos e ON p.PokedexId=e.PokedexId
                 SET p.PokedexId= e.PokedexIdNew WHERE p.PokemonId = ? and e.EvoType= ? ;";
        $stmtevo = mysqli_prepare($conn, $queryevo);
        mysqli_stmt_bind_param($stmtevo, 'is', $pokemonId, $evoType);
        $resultevo = mysqli_stmt_execute($stmtevo);
        mysqli_stmt_close($stmtevo);
        if (!$resultevo) {
            echo json_encode(array('success' => false, 'message' => 'Can\'t evolve'));
            return array('success' => false);
        }

        $evoInfo = 'Pokemon(' . $pokemonId . ') evolved!';

        logServerMessage($evoInfo);
        addMessage($evoInfo);

        fillMonStats($pokemonId);

        $result = array('success' => true, 'message' => $evoInfo);

        echo json_encode($result) . "\n";
        return $result;
    }
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

function addExp($pokemonId, $expGained, $token)
{
    global $conn;

    if (!belongsToUser($pokemonId, $token)) {
        echo json_encode(array('success' => false, 'message' => 'Pokemon does not belogns to user!')) . "\n";
        $result = array('success' => false);
        return $result;
    } else {
        $expdet = getPokemonExpDetails($pokemonId);
        $maxlevel = 100;

        if (($expdet['success'] && $expGained > 0) && $expdet['level'] < $maxlevel) {
            $expTNL = $expdet['expTNL'];
            $level = $expdet['level'];
            if ($expGained >= $expTNL) {
                $querylvl = "UPDATE pokemon SET Level = (Level + 1) WHERE PokemonId = ?";
                $stmtlvl = mysqli_prepare($conn, $querylvl);
                mysqli_stmt_bind_param($stmtlvl, 'i', $pokemonId);
                $resultlvl = mysqli_stmt_execute($stmtlvl);
                mysqli_stmt_close($stmtlvl);

                if (!$resultlvl) {
                    echo json_encode(array('success' => false, 'message' => 'Can\'t level up' . "\n"));
                    return array('success' => false);
                }
                $expGained -= $expTNL;
                $expToAdd = 0;
                $level++;

                fillMonStats($pokemonId);

                $result['pokemonId'] = $pokemonId;
                $result['levelup'] = true;
                $result['expToAdd'] =  $expGained;
                $result['evolve'] = canEvolve($pokemonId);

                $newMove = canLearnMove($pokemonId);
                print_r($newMove);
                if (!empty($newMove['moves'])) {
                    if ($newMove['moveCount'] >= 4) {
                        $result['moveSwap'] = [];
                        foreach ($newMove['moves'] as $moveId) {
                            array_push($result['moveSwap'], $moveId);
                        }
                    } else {
                        $orderNumber = $newMove['moveCount'] + 1;
                        foreach ($newMove['moves'] as $moveId) {
                            if ($orderNumber <= 4) {
                                learnMove($pokemonId, $moveId, $orderNumber, $token);
                                $orderNumber++;
                            } else {
                                array_push($result['moveSwap'], $moveId);
                            }
                        }
                    }
                }
            } else {
                $expToAdd = $expGained;
                $expGained = 0;
            }
            $queryexp = "UPDATE pokemon SET Exp = Exp + ? WHERE PokemonId = ?";
            $stmtexp = mysqli_prepare($conn, $queryexp);
            mysqli_stmt_bind_param($stmtexp, 'ii', $expToAdd, $pokemonId);
            $resultexp = mysqli_stmt_execute($stmtexp);
            mysqli_stmt_close($stmtexp);
        } else {
            $result['message'] = 'level capped';
        }
        $result['success'] = true;
        return $result;
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


    $query = "SELECT MoveId, Level FROM learnset WHERE PokedexId= ? and Level<= ? Order by Level desc limit 4";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $pokedexId, $level);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $movecount = 1;

    while ($moveData = mysqli_fetch_assoc($result)) {
        learnMove($lastInsertId, $moveData['MoveId'], $movecount, $token);
        $movecount++;
    }

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
        $query = "UPDATE pokemon SET Released = 1, ReleaseDate = CURRENT_TIMESTAMP() WHERE PokemonId = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(array('success' => true)) . "\n";
        logServerMessage("Released Pokémon with ID: $pokemonId", 'INFO');
        return $result;
    }
}

function learnMove($pokemonId, $moveId, $orderNumber, $token)
{
    global $conn;

    if (!belongsToUser($pokemonId, $token)) {
        $response = array('success' => false, 'message' => 'Pokemon does not belong to user!');
        return $response;
    } else {
        $existingMovesQuery = "SELECT MoveId, MoveOrder FROM movesets WHERE PokemonId = ?";
        $existingMovesStmt = mysqli_prepare($conn, $existingMovesQuery);
        mysqli_stmt_bind_param($existingMovesStmt, 'i', $pokemonId);
        mysqli_stmt_execute($existingMovesStmt);
        $existingMovesResult = mysqli_stmt_get_result($existingMovesStmt);
        $existingMoves = mysqli_fetch_all($existingMovesResult, MYSQLI_ASSOC);
        mysqli_stmt_close($existingMovesStmt);

        $existingMoveIds = array_column($existingMoves, 'MoveId');
        $existingMoveOrders = array_column($existingMoves, 'MoveOrder');

        if (in_array($moveId, $existingMoveIds)) {
            $updateQuery = "
            UPDATE movesets
            SET MoveOrder = 
                CASE 
                    WHEN MoveId = ?  THEN ?
                    WHEN MoveOrder = ? THEN 0
                END
            WHERE PokemonId = ? AND (MoveId = ? OR MoveOrder = ?);
            ";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, 'iiiiii', $moveId, $orderNumber, $orderNumber, $pokemonId, $moveId, $orderNumber);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);

            $response = array('success' => true, 'message' => 'Move added in the moveset!');
            logServerMessage("Move updated in the moveset: $pokemonId", 'INFO');
            return $response;
        } else {
            if (in_array($orderNumber, $existingMoveOrders)) {
                $updateQuery = "UPDATE movesets ms
                INNER JOIN moves m ON ms.MoveId = ms.MoveId
                SET ms.MoveId = ?, ms.PPValue = m.PP, ms.PP = m.PP
                WHERE ms.PokemonId = ? AND ms.MoveOrder = ?";
                $updateStmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($updateStmt, 'iii', $moveId, $pokemonId, $orderNumber);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);

                $response = array('success' => true, 'message' => 'Move added in the moveset!');
                logServerMessage("Move updated in the moveset: $pokemonId", 'INFO');
                return $response;
            } else {
                $insertQuery = "INSERT INTO movesets (PokemonId, MoveId, PPValue, PP, MoveOrder)
                                SELECT ?, ?, PP, PP, ? FROM moves WHERE MoveId = ?";
                $insertStmt = mysqli_prepare($conn, $insertQuery);
                mysqli_stmt_bind_param($insertStmt, 'iiii', $pokemonId, $moveId, $orderNumber, $moveId);
                mysqli_stmt_execute($insertStmt);
                mysqli_stmt_close($insertStmt);

                $response = array('success' => true, 'message' => 'New move added to the moveset!');
                logServerMessage("New move added to the moveset: $pokemonId", 'INFO');
                return $response;
            }
        }
    }
}

function canLearnMove($pokemonId)
{
    global $conn;

    $query = "
        SELECT ls.MoveId 
        FROM pokemon p
        JOIN learnset ls ON p.PokedexId = ls.PokedexId
        WHERE p.PokemonId = ? AND ls.Level = p.Level";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $moves = [];
    while ($moveData = mysqli_fetch_assoc($result)) {
        $moves[] = $moveData['MoveId'];
    }

    mysqli_stmt_close($stmt);

    $queryms = "
        SELECT COUNT(*) as moveCount 
        FROM movesets 
        WHERE PokemonId = ? AND MoveOrder != 0";

    $stmtms = mysqli_prepare($conn, $queryms);
    mysqli_stmt_bind_param($stmtms, 'i', $pokemonId);
    mysqli_stmt_execute($stmtms);
    $resultms = mysqli_stmt_get_result($stmtms);

    $movesetData = mysqli_fetch_assoc($resultms);
    mysqli_stmt_close($stmtms);

    $movesetCount = intval($movesetData['moveCount']);

    return !empty($moves) ? ['moves' => $moves, 'moveCount' => $movesetCount] : false;
}
