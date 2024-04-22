<?php
require 'settings/conn.php';
require 'func.php';
session_start();

$pokedexId = $_POST['pokedexId'];
$level = $_POST['level'];
$inparty = isPartyFull();

$query = "INSERT INTO pokemon (`UserId`, `PokedexId`, `Level`, `Status`, `ItemHeld`, `inParty`, `Released`) 
          VALUES (?, ?, ?, 'OK', 0, ?, 0)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'iiii', $_SESSION['userid'], $pokedexId, $level, $inparty);
$result = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$lastInsertId = mysqli_insert_id($conn);

$currentHP = getCurrentHP($lastInsertId);

if ($result && $lastInsertId) {
    fillMonStats($lastInsertId);
}

if ($result) {
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('success' => false));
}

if ($inparty) {
    addMessage('Pokemon joined the party!');
} else {
    addMessage('Team is full! Pokemon was put into the box!');
}
