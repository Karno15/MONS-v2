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
    mysqli_stmt_bind_result($stmt, $avatarPath);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $avatarPath;
}

?>