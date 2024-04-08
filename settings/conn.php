<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "monsv2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ftp_server = "localhost";
$ftp_username = "admin";
$ftp_password = "admin";

$ftp_conn = ftp_connect($ftp_server);
if (!$ftp_conn) {
    die("Failed to connect to FTP server");
}

$login_result = ftp_login($ftp_conn, $ftp_username, $ftp_password);
if (!$login_result) {
    die("Failed to login to FTP server");
}

function uidExists($uid)
{
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE UID = ?");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    return false;
}
