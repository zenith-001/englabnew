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

    // Handle file upload
    $target_file = 'uploads/' . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $stmt = $pdo->prepare("INSERT INTO movies (name, description, file) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $target_file]);
        file_put_contents('error.log', "Uploaded: $name\n", FILE_APPEND);
    } else {
        file_put_contents('error.log', "Upload failed for: $name\n", FILE_APPEND);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Movie</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Upload Movie</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Movie Name" required>
        <textarea name="description" placeholder="Movie Description" required></textarea>
        <input type="file" name="movie_file" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>