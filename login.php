<?php
require_once "settings/conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["login"]) || empty($_POST["password"])) {
        header("Location: index.php");
        exit;
    }

    $login = $_POST["login"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT password, uid FROM users WHERE login = ? and status='A'");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            session_start();
            $_SESSION["uid"] = $row["uid"];
            header("Location: main.php");
            exit;
        } else {
            header("Location: index.php?error=incorrect");
            exit;
        }
    } else {
        header("Location: index.php?error=not_found");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
