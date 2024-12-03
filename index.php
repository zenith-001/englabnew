<?php
include 'db.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = $pdo->prepare("SELECT * FROM movies WHERE name LIKE ?");
$query->execute(["%$search%"]);
$movies = $query->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Movie Browser</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Movie Browser</h1>
    <form method="GET">
        <input type="text" name="search" placeholder="Search for a movie" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>
    <ul>
        <?php foreach ($movies as $movie): ?>
            <li><a href="watch.php?id=<?= $movie['id'] ?>"><?= htmlspecialchars($movie['name']) ?></a></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>