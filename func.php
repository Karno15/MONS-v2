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

function saveUserAction($payload, $token)
{
    global $conn;

    $data = json_decode($payload, true);
    $actions = [];
    $insertIds = [];

    if ($data['levelup'] > 0 || $data['expToAdd'] > 0) {
        $combinedPayload = [
            'pokemonId' => $data['pokemonId']
        ];

        if ($data['levelup'] > 0) {
            $combinedPayload['levelup'] = $data['levelup'];
        }

        if ($data['expToAdd'] > 0) {
            $combinedPayload['expToAdd'] = $data['expToAdd'];
        }

        $actions['levelup'] = json_encode($combinedPayload);
    }

    if (!empty($data['learned'])) {
        foreach ($data['learned'] as $moveId) {
            $actions['learned_' . $moveId] = json_encode([
                'learned' => $moveId,
                'pokemonId' => $data['pokemonId']
            ]);
        }
    }

    if (!empty($data['moveSwap'])) {
        foreach ($data['moveSwap'] as $moveId) {
            $actions['moveSwap_' . $moveId] = json_encode([
                'moveSwap' => $moveId,
                'pokemonId' => $data['pokemonId']
            ]);
        }
    }

    $tokenData = getTokenData($token);
    $userId = $tokenData['userid'];

    if ($data['evolve'] > 0) {
        $evolvePayload = json_encode([
            'evolve' => $data['evolve'],
            'pokemonId' => $data['pokemonId']
        ]);
        $checkQuery = "SELECT 1 FROM useractions WHERE UserId = ? AND Done = 0 AND JSON_EXTRACT(Payload, '$.evolve') = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param('is', $userId, $data['evolve']);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows === 0) {
            $actions['evolve'] = $evolvePayload;
        }
        $checkStmt->close();
    }

    if (empty($actions)) {
        return 0;
    }

    $query = "INSERT INTO useractions (`UserId`, `Payload`) VALUES (?, ?)";
    $stmt = $conn->prepare($query);

    foreach ($actions as $key => $action) {
        $stmt->bind_param('is', $userId, $action);
        $result = $stmt->execute();
        if ($result) {
            $insertIds[$key] = $conn->insert_id;
        }
    }

    $stmt->close();
    return !empty($insertIds) ? $insertIds : 0;
}


function confirmAction($actionIdInput, $token)
{
    global $conn;

    $tokenData = getTokenData($token);
    $userId = $tokenData['userid'];

    $actionIds = [];

    if (is_array($actionIdInput)) {
        foreach ($actionIdInput as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subValue) {
                    $actionIds[] = $subValue;
                }
            } else {
                $actionIds[] = $value;
            }
        }
    } else {
        $actionIds[] = $actionIdInput;
    }

    if (empty($actionIds)) {
        return false;
    }

    $query = "UPDATE useractions SET Done = 1 WHERE UserId = ? AND UserActionId = ?";
    $stmt = mysqli_prepare($conn, $query);

    $result = 1;

    foreach ($actionIds as $actionId) {
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $actionId);
        $executeResult = mysqli_stmt_execute($stmt);
        if (!$executeResult) {
            $result = 0;
        }
    }

    mysqli_stmt_close($stmt);

    return $result;
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

    $query = 'SELECT e.PokedexIdNew 
    FROM `evos` e JOIN pokemon p ON p.PokedexId=e.PokedexId
    WHERE EvoType="EXP" AND p.Level>=e.LevelReq AND p.PokemonId= ? ';

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return $rows[0]['PokedexIdNew'];
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

        $newMove = canLearnMove($pokemonId);
        $resultMove['moveSwap'] = [];
        $resultMove['learned'] = [];
        $resultMove['pokemonId'] = $pokemonId;
        print_r($newMove);
        if (!empty($newMove['moves'])) {
            if ($newMove['moveCount'] >= 4) {
                foreach ($newMove['moves'] as $moveId) {
                    array_push($resultMove['moveSwap'], $moveId);
                }
            } else {
                $orderNumber = $newMove['moveCount'] + 1;
                foreach ($newMove['moves'] as $moveId) {
                    if ($orderNumber <= 4) {
                        learnMove($pokemonId, $moveId, $orderNumber, $token);
                        array_push($resultMove['learned'], $moveId);
                        $orderNumber++;
                    } else {
                        array_push($resultMove['moveSwap'], $moveId);
                    }
                }
            }
            saveUserAction(json_encode($resultMove), $token);
        }

        $result = array('success' => true, 'message' => $evoInfo, 'pokemonId' => $pokemonId);

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

                $result['levelup'] = $level;
                $result['expToAdd'] =  $expGained;
                $evolution = canEvolve($pokemonId);

                $newMove = canLearnMove($pokemonId);
                $result['moveSwap'] = [];
                $result['learned'] = [];
                print_r($newMove);
                if (!empty($newMove['moves'])) {
                    if ($newMove['moveCount'] >= 4) {
                        foreach ($newMove['moves'] as $moveId) {
                            array_push($result['moveSwap'], $moveId);
                        }
                    } else {
                        $orderNumber = $newMove['moveCount'] + 1;
                        foreach ($newMove['moves'] as $moveId) {
                            if ($orderNumber <= 4) {
                                learnMove($pokemonId, $moveId, $orderNumber, $token);
                                array_push($result['learned'], $moveId);
                                $orderNumber++;
                            } else {
                                array_push($result['moveSwap'], $moveId);
                            }
                        }
                    }
                }

                $query = "CALL fillMonExp(?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
                mysqli_stmt_execute($stmt);
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

        $result['pokemonId'] = $pokemonId;
        $result['evolve'] = $evolution ?? 0;
        $result['expToAdd'] = $result['expToAdd'] ?? 0;
        $result['levelup'] = $result['levelup'] ?? 0;
        $result['success'] = true;
        $result['actionId'] = saveUserAction(json_encode($result), $token);
        return $result;
    }
}

function addMon($pokedexId, $level, $nick, $token)
{
    global $conn;

    $tokenData = getTokenData($token);
    $userId = $tokenData['userid'];
    $inparty = isPartyFull($userId);
    $query = "INSERT INTO pokemon (`Nickname`,`UserId`, `PokedexId`, `Level`, `Status`, `ItemHeld`, `inParty`, `Released`) 
          VALUES (?, ?, ?, ?, 'OK', 0, ?, 0)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'siiii', $nick, $userId, $pokedexId, $level, $inparty);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $lastInsertId = mysqli_insert_id($conn);

    $query = "CALL fillMonExp(?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $lastInsertId);
    mysqli_stmt_execute($stmt);

    $query = "SELECT MoveId, MAX(Level) as Level 
    FROM learnset 
    WHERE PokedexId = ? AND Level <= ?
    GROUP BY MoveId
    ORDER BY Level DESC
    LIMIT 4;";

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
    }

    $existingMovesQuery = "SELECT MoveId, MoveOrder FROM movesets WHERE PokemonId = ?";
    $existingMovesStmt = mysqli_prepare($conn, $existingMovesQuery);
    mysqli_stmt_bind_param($existingMovesStmt, 'i', $pokemonId);
    mysqli_stmt_execute($existingMovesStmt);
    $existingMovesResult = mysqli_stmt_get_result($existingMovesStmt);
    $existingMoves = mysqli_fetch_all($existingMovesResult, MYSQLI_ASSOC);
    mysqli_stmt_close($existingMovesStmt);

    $existingMoveIds = array_column($existingMoves, 'MoveId');
    $existingMoveOrders = array_column($existingMoves, 'MoveOrder');

    if ($orderNumber == 0) {
        if (in_array($moveId, $existingMoveIds)) {
            return array('success' => true, 'message' => 'Move already exists in the moveset!');
        } else {
            $insertQuery = "INSERT INTO movesets (PokemonId, MoveId, PPValue, PP, MoveOrder)
                            SELECT ?, ?, PP, PP, 0 FROM moves WHERE MoveId = ?";
            $insertStmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, 'iii', $pokemonId, $moveId, $moveId);
            mysqli_stmt_execute($insertStmt);
            mysqli_stmt_close($insertStmt);

            return array('success' => true, 'message' => 'New move added to the moveset with order 0!');
        }
    } else {
        if (!in_array($moveId, $existingMoveIds)) {
            $insertQuery = "INSERT INTO movesets (PokemonId, MoveId, PPValue, PP, MoveOrder)
                            SELECT ?, ?, PP, PP, 0 FROM moves WHERE MoveId = ?";
            $insertStmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, 'iii', $pokemonId, $moveId, $moveId);
            mysqli_stmt_execute($insertStmt);
            mysqli_stmt_close($insertStmt);
        }

        $currentMoveQuery = "SELECT MoveId FROM movesets WHERE PokemonId = ? AND MoveOrder = ?";
        $currentMoveStmt = mysqli_prepare($conn, $currentMoveQuery);
        mysqli_stmt_bind_param($currentMoveStmt, 'ii', $pokemonId, $orderNumber);
        mysqli_stmt_execute($currentMoveStmt);
        $currentMoveResult = mysqli_stmt_get_result($currentMoveStmt);
        $currentMove = mysqli_fetch_assoc($currentMoveResult);
        mysqli_stmt_close($currentMoveStmt);

        if ($currentMove) {
            $oldMoveId = $currentMove['MoveId'];

            $updateQuery = "
            UPDATE movesets
            SET MoveOrder = CASE
                WHEN MoveId = ? THEN 0
                WHEN MoveId = ? THEN ?
            END
            WHERE PokemonId = ? AND (MoveId = ? OR MoveId = ?)";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, 'iiiiii', $oldMoveId, $moveId, $orderNumber, $pokemonId, $oldMoveId, $moveId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);

            return array('success' => true, 'message' => 'Move order swapped successfully!');
        } else {
            $updateQuery = "UPDATE movesets SET MoveOrder = ? WHERE PokemonId = ? AND MoveId = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, 'iii', $orderNumber, $pokemonId, $moveId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);

            return array('success' => true, 'message' => 'Move order set successfully!');
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
    LEFT JOIN movesets ms ON ms.MoveId = ls.MoveId AND ms.PokemonId = p.PokemonId
    WHERE p.PokemonId = ? AND ls.Level = p.Level AND ms.MoveId IS NULL;";

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

    $result = !empty($moves) ? ['moves' => $moves, 'moveCount' => $movesetCount] : false;
    return $result;
}
