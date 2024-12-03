<?php
include 'db.php';
session_start();

// Fetch movie details based on ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    $movie = $stmt->fetch();

    if (!$movie) {
        echo "Movie not found.";
        exit();
    }

    // Log movie ID
    file_put_contents('error.log', "Watching movie ID: $id\n", FILE_APPEND);
} else {
    echo "No movie specified.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Watch Movie</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: Arial, sans-serif;
        }
        #video-player {
            width: 800px; /* Fixed width */
            height: 450px; /* Fixed height */
            margin: auto;
            border: 2px solid #444;
            background-color: #000;
        }
        h1 {
            text-align: center;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($movie['name']); ?></h1>
    <div id="video-player">
        <video controls width="800" height="450">
            <source src="<?php echo htmlspecialchars($movie['file']); ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    <p><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
</body>
</html>