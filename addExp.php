<?php
require 'settings/conn.php';
require 'func.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pokemonId = $_POST['pokemonId'];
    $exp = $_POST['exp'];

    $query = 'select p.Level,p.Exp,e.Exp as "Explevel", (e.Exp+e.ExpTNL-p.Exp) as "ExpTNL" from pokemon p join pokedex pk
     ON pk.PokedexId=p.PokedexId JOIN exptype e ON e.Level=p.Level and pk.ExpType=e.Type where pokemonId= ? ;';
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $pokemonId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        $exp = $row['Exp'];
        $explevel = $exp = $row['Exp'];
        $level = $row['Level'];
        $exp = $row['ExpTNL'];

    } else {
        echo json_encode(array('success' => false, 'message' => 'Cant update exp'));
    }

    print_r($row);

    $query = "UPDATE pokemon SET Exp = Exp + ? WHERE PokemonId = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $exp, $pokemonId);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Cant update exp'));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Cant add exp'));
}
