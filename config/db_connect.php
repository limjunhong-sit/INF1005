<?php
session_set_cookie_params(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 1. Local Credentials (since PHP and MySQL are on the same machine)
$host = 'localhost';          // This is the magic word that tells PHP to look inside its own computer
$dbname = 'UniClothes';       // The exact name of the database
$username = 'inf1005-sqldev';  // Ask the database creator for the SQL username (often 'root' or a custom one)
$password = 'infA1005!';  // The password for that SQL user

// 2. Establish the Connection
try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3 // Keeps the 3-second fail-safe just in case
    ];
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
    
} catch (PDOException $e) {
    die("Local Database Connection Failed: " . $e->getMessage());
}
?>
