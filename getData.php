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
        if ($stmt = $conn->prepare('select m.MoveId, m.Name, m.Effect, m.PP, m.Power, m.Accuracy, m.Description, t.Name as "TypeName",t.TypeColor
         from moves m JOIN types t ON t.TypeId=m.Type where m.MoveId= ?;')) {
            $stmt->bind_param("i", $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        break;
    case 'pokedexId':
        if ($stmt = $conn->prepare("SELECT * FROM pokedex WHERE PokedexId = ?")) {
            $stmt->bind_param("i", $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        break;
    case 'moves':
        if ($stmt = $conn->prepare("CALL showPokemonMoves(?)")) {
            mysqli_stmt_bind_param($stmt, 'i', $value);
            mysqli_stmt_execute($stmt);
            $movesResult = mysqli_stmt_get_result($stmt);
            $movesData = array();
            while ($movesRow = mysqli_fetch_assoc($movesResult)) {
                $movesData[] = $movesRow;
            }
            mysqli_stmt_close($stmt);
            $response = $movesData;
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
