<?php
// Database connection details
$host = 'localhost';
$db = 'movie_database';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents('error.log', $e->getMessage(), FILE_APPEND);
    die("Database connection failed.");
}

// FTP configuration
$ftp_server = "localhost"; // Replace with your FTP server
$ftp_user = "zenith"; // Replace with your FTP username
$ftp_pass = "8038@Zenith"; // Replace with your FTP password

?>