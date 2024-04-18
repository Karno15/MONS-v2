<?php
require 'settings/conn.php';
require 'func.php';
session_start();

if(!isset($_SESSION['userid'])){
    echo json_encode(array('error' => 'No Access!'));
    header('Location:index.php');
    exit;
}

$query = "CALL showPartyData(?)";
$stmt = mysqli_prepare($conn, $query);
$userID = $_SESSION["userid"];
mysqli_stmt_bind_param($stmt, 'i', $userID);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pokemonData = array();
while ($row = mysqli_fetch_assoc($result)) {
    $pokemonData[] = $row;
}
mysqli_stmt_close($stmt);

if ($pokemonData !== false) {
    header('Content-Type: application/json');
    echo json_encode($pokemonData);
} else {
    echo json_encode(array('error' => 'Failed to fetch party Pokemon data'));
}
?>
