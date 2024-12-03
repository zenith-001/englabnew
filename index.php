<?php
include 'db.php';
session_start();

// Fetch all movies
$stmt = $pdo->query("SELECT * FROM movies");
$movies = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Movie List</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .movie-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .movie-item {
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
            background: #f8f8f8;
            transition: transform 0.3s;
        }
        .movie-item:hover {
            transform: scale(1.05);
        }
        .movie-item img {
            width: 100%;
            height: auto;
        }
        .movie-title {
            font-weight: bold;
            padding: 10px;
        }
        .watch-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 10px;
            text-decoration: none;
        }
        .watch-button i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <h1>Movie List</h1>
    <div class="movie-list">
        <?php foreach ($movies as $movie): ?>
            <div class="movie-item">
                <img src="thumbnail.jpg" alt="Thumbnail"> <!-- Replace with actual thumbnail if available -->
                <div class="movie-title"><?php echo htmlspecialchars($movie['name']); ?></div>
                <a href="watch.php?id=<?php echo $movie['id']; ?>" class="watch-button">
                    <i class="fas fa-play"></i> Watch
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>