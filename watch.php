<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $query->execute([$id]);
    $movie = $query->fetch();

    if (!$movie) {
        die("Movie not found.");
    }
} else {
    die("No movie selected.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Watch Movie</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1><?= htmlspecialchars($movie['name']) ?></h1>
    <video controls>
        <source src="<?= htmlspecialchars($movie['file']) ?>" type="video/mp4">
        Your browser does not support the video tag.
    </video>
</body>
</html>