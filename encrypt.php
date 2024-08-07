<?php
function encrypt($data) {
    $password = getenv('ENCRYPTION_PASSWORD');
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $password, 0, $iv);
    return base64_encode($iv . $encryptedData);
}

function decrypt($encryptedData) {
    $password = getenv('ENCRYPTION_PASSWORD');
    $encryptedData = base64_decode($encryptedData);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($encryptedData, 0, $ivLength);
    $encryptedData = substr($encryptedData, $ivLength);
    return openssl_decrypt($encryptedData, 'aes-256-cbc', $password, 0, $iv);
}
?>