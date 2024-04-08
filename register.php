<?php
require_once "settings/conn.php";

function generateUID()
{
    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST["login"]) || empty($_POST["password"])) {
        header("Location: index.php");
        exit;
    }
    $login = $_POST["login"];
    $password = $_POST["password"];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $status = 'A';
    $uid = generateUID();

    $stmt = $conn->prepare("INSERT INTO users (login, password, status, UID) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $login, $hashedPassword, $status, $uid);
    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
} else {
    header("Location: index.php");
    exit;
}
