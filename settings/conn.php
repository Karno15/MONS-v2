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
