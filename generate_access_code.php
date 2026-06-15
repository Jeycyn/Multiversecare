<?php
function generateSecureAccessCode($length = 10) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    return substr(str_shuffle($characters), 0, $length);
}

$raw_code = generateSecureAccessCode();
$hashed_code = password_hash($raw_code, PASSWORD_DEFAULT);

// Show what to store and give to the admin
echo "Access Code to give admin: $raw_code\n";
echo "Store this in DB (access_code field):\n$hashed_code";
