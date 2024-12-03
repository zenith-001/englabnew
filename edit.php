<?php
include 'db.php';
session_start();

// Password protection
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $query->execute([$id]);
    $movie = $query->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete'])) {
            // Delete movie
            $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
            $stmt->execute([$id]);
            file_put_contents('error.log', "Deleted movie ID: $id\n", FILE_APPEND);
            header('Location: index.php');
            exit();
        } else {
            // Update movie details
            $name = $_POST['name'];
            $description = $_POST['description'];
            $stmt = $pdo->prepare("UPDATE movies SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            file_put_contents('error.log', "Updated movie ID: $id\n", FILE_APPEND);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Movie</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Edit Movie</h1>
    <form method="POST">
        <input type="text" name="name" value="<?= htmlspecialchars($movie['name']) ?>" required>
        <textarea name="description" required><?= htmlspecialchars($movie['description']) ?></textarea>
        <button type="submit">Update</button>
        <button type="submit" name="delete">Delete</button>
    </form>
</body>
</html>