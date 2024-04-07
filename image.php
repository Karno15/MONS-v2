<?php

require('settings/conn.php');

if (!isset($_GET['path'])) {
    echo "No access";
    exit();
}

$remoteImageFilePath = $_GET['path'];
$imageContent = getImageContent($ftp_conn, $remoteImageFilePath);

if ($imageContent !== false) {
    $fileExtension = pathinfo($remoteImageFilePath, PATHINFO_EXTENSION);
    $contentType = getContentType($fileExtension);

    if ($contentType !== false) {
        header("Content-Type: $contentType");
        $tempFile = tempnam(sys_get_temp_dir(), 'image_');
        file_put_contents($tempFile, file_get_contents($imageContent));
        readfile($tempFile);
        ftp_close($ftp_conn);
        unlink($tempFile);
    } else {
        echo 'Unsupported file extension';
    }
} else {
    echo 'Error fetching image content';
}

function getImageContent($ftpConn, $remoteFilePath)
{
    $tempFile = tempnam(sys_get_temp_dir(), 'ftp_image_');

    if (ftp_get($ftpConn, $tempFile, $remoteFilePath, FTP_BINARY)) {
        return $tempFile;
    } else {
        return false;
    }
}

function getContentType($fileExtension)
{
    switch ($fileExtension) {
        case 'png':
            return 'image/png';
        case 'jpg':
        case 'jpeg':
            return 'image/jpeg';
        case 'gif':
            return 'image/gif';
        default:
            return false;
    }
}
