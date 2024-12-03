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
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: Arial, sans-serif;
        }
        .search-bar {
            max-width: 600px;
            margin: 20px auto;
            padding: 10px;
            background-color: #1e1e1e;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        .search-bar input {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #2a2a2a;
            color: #ffffff;
        }
        .movie-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .movie-item {
            border: 1px solid #444;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
            background: #1e1e1e;
            padding: 12px;
            padding-bottom:32px;
            transition: transform 0.3s;
        }
        .movie-item:hover {
            transform: scale(1.05);
        }
        .movie-title {
            font-weight: bold;
            padding: 10px;
            color: #ffffff;
            margin-bottom:25px;
        }
        .watch-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
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
    
    <!-- Search Bar -->
    <div class="search-bar">
        <input type="text" id="search" placeholder="Search for movies...">
    </div>

    <div class="movie-list" id="movie-list">
        <?php foreach ($movies as $movie): ?>
            <div class="movie-item">
                <div class="movie-title"><?php echo htmlspecialchars($movie['name']); ?></div>
                <a href="watch.php?id=<?php echo $movie['id']; ?>" class="watch-button">
                    <i class="fas fa-play"></i> Watch
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Search functionality
        document.getElementById('search').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const movieItems = document.querySelectorAll('.movie-item');

            movieItems.forEach(item => {
                const title = item.querySelector('.movie-title').textContent.toLowerCase();
                item.style.display = title.includes(query) ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>