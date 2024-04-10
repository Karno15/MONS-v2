<?php

function getAvatarPath($userId) {
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
function getSignature($userId) {
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

function callProc($procName, $params = array()) {
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


?>