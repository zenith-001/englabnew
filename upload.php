<?php
include 'db.php';
session_start();

// Password protection
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $file = $_FILES['movie_file'];

    // Handle file upload in chunks
    $upload_dir = 'uploads/';
    $chunk_size = 100 * 1024 * 1024; // 100 MB
    $total_size = $file['size'];
    $chunks = ceil($total_size / $chunk_size);
    $file_name = $upload_dir . basename($file['name']);

    // Open a file to write
    $out = fopen($file_name, 'wb');
    if ($out) {
        // Read the file in chunks
        $handle = fopen($file['tmp_name'], 'rb');
        if ($handle) {
            $uploaded = 0;
            while (!feof($handle)) {
                $buffer = fread($handle, $chunk_size);
                fwrite($out, $buffer);
                $uploaded += strlen($buffer);
                // Calculate progress
                $progress = ($uploaded / $total_size) * 100;
                file_put_contents('error.log', "Uploaded: " . round($progress ⬤