<?php
require 'settings/conn.php';
require 'func.php';
session_start();

if(!isset($_SESSION['userid'])){
    echo json_encode(array('error' => 'No Access!'));
    header('Location:index.php');
    exit;
}

$pokemonData = callProc('showPartyData', array(
    array('value' => $_SESSION["userid"], 'type' => 'i')
));

if ($pokemonData !== false) {
    header('Content-Type: application/json');
    echo json_encode($pokemonData);
} else {
    echo json_encode(array('error' => 'Failed to fetch party Pokemon data'));
}
?>
