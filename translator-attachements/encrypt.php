<?php

$password="vmUeu7D9bzE5JmNE";
$data=json_decode(file_get_contents('php://input'));
$plaintext=$data->user;

function encryptString($plaintext, $password) {
    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, "AES-256-CBC", hash('sha256', $password, true), OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext . $iv, hash('sha256', $password, true), true);
    return base64_encode($iv.$hmac.$ciphertext);
}

echo encryptString($plaintext,$password);

?>
