<?php
session_start();

if (!isset($_SESSION['userid'])) {
    exit(json_encode(['error' => 'No access!']));
}

if (!isset($_POST['key']) || !isset($_POST['value'])) {
    exit(json_encode(['error' => 'Invalid request!']));
}

require_once 'settings/conn.php';

$key = $_POST['key'];
$value = $_POST['value'];
$response = [];

switch ($key) {
    case 'pokemonId':
        if ($stmt = $conn->prepare("SELECT pk.Name, p.* FROM pokemon p JOIN pokedex pk ON pk.pokedexId = p.pokedexid WHERE pokemonId = ?")) {
            $stmt->bind_param("i", $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        break;
    case 'moveId':
        if ($stmt = $conn->prepare("SELECT * FROM moves WHERE MoveId = ?")) {
            $stmt->bind_param("i", $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        break;
    case 'pokedexId':
        if ($stmt = $conn->prepare("SELECT * FROM pokedex WHERE PokedexId = ?")) {  // Corrected table name to 'pokedex'
            $stmt->bind_param("i", $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        break;
    default:
        exit(json_encode(['error' => 'Invalid key!']));
}

$conn->close();

if (empty($response)) {
    exit(json_encode(['error' => 'No data found!']));
}

echo json_encode($response);
