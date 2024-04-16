<?php

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

function callProc($procName, $params = array())
{
    global $conn;

    $paramTypes = '';
    $paramValues = array();
    $placeholders = '';

    foreach ($params as $param) {
        $paramTypes .= $param['type'];
        $paramValues[] = &$param['value'];
        $placeholders .= '?,';
    }
    $placeholders = rtrim($placeholders, ',');
    $query = "CALL $procName($placeholders)";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        return false;
    }

    if (!empty($paramTypes)) {
        array_unshift($paramValues, $stmt, $paramTypes);
        call_user_func_array('mysqli_stmt_bind_param', $paramValues);
    }
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

function isPartyFull()
{
    $result = callProc('showPartyData', array(
        array('value' => $_SESSION["userid"], 'type' => 'i')
    ));

    if ($result !== false && count($result) < 6) {
        return true;
    } else {
        return false;
    }
}

function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

function playerDefeatedOpponent()
{
    if (isset($_SESSION['addexp']) && $_SESSION['addexp']) {
        return true;
    } else {
        return false;
    }
}
