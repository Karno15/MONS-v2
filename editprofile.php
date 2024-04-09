<?php
require 'settings/conn.php';

session_start();

// Check if file is provided
if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'] == UPLOAD_ERR_OK) {
    $tmpFilePath = $_FILES['fileInput']['tmp_name'];
    $fileName = $_FILES['fileInput']['name'];
    $ftpUploadDirectory = "/avatars/";

    $uniqueFileName = $_SESSION['userid'] . '.' . pathinfo($fileName, PATHINFO_EXTENSION); // Use the file's original extension
    $destinationPath = $ftpUploadDirectory . $uniqueFileName;

    if (ftp_put($ftp_conn, $destinationPath, $tmpFilePath, FTP_BINARY)) {
        echo json_encode(array('success' => true, 'message' => 'File uploaded successfully.', 'filePath' => $destinationPath));

        $updateQuery = "UPDATE users SET avatar = ?, updated = CURRENT_TIMESTAMP() WHERE userid = ?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        $avatarPath = $destinationPath;
        $userId = $_SESSION['userid'];
        mysqli_stmt_bind_param($stmt, "si", $avatarPath, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to upload the file to FTP server.'));
    }
}

// Check if signature is provided
if (isset($_POST['signature'])) {
    $signature = $_POST['signature'];

    // Update signature in the database
    $updateSignatureQuery = "UPDATE users SET signature = ?, updated = CURRENT_TIMESTAMP() WHERE userid = ?";
    $stmt = mysqli_prepare($conn, $updateSignatureQuery);
    $userId = $_SESSION['userid'];
    mysqli_stmt_bind_param($stmt, "si", $signature, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header('Location:main.php');
