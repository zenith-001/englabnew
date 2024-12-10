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
            <source src="<?php echo htmlspecialchars($movie['file_path']); ?>" type="video/mp4">
            
            <?php 
            // Add subtitle track if subtitle exists
            if (!empty($movie['subtitle_path'])) {
                $subtitleExtension = pathinfo($movie['subtitle_path'], PATHINFO_EXTENSION);
                $subtitleMimeType = '';
                
                // Determine subtitle mime type based on file extension
                switch (strtolower($subtitleExtension)) {
                    case 'srt':
                        $subtitleMimeType = 'text/srt';
                        break;
                    case 'vtt':
                        $subtitleMimeType = 'text/vtt';
                        break;
                    case 'webvtt':
                        $subtitleMimeType = 'text/vtt';
                        break;
                    default:
                        $subtitleMimeType = 'text/plain';
                }
                
                // Determine language (you might want to add a language column to your database)
                $subtitleLanguage = 'en'; // Default to English, can be made dynamic
                
                echo '<track 
                    kind="subtitles" 
                    src="' . htmlspecialchars($movie['subtitle_path']) . '" 
                    srclang="' . $subtitleLanguage . '" 
                    label="' . ucfirst($subtitleLanguage) . '" 
                    type="' . $subtitleMimeType . '"
                >';
            }
            ?>
            
            Your browser does not support the video tag.
        </video>
    </div>
    <p><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const video = document.querySelector('video');
        const subtitleTrack = video.querySelector('track');

        if (subtitleTrack) {
            // Optional: Set the first subtitle track as default
            subtitleTrack.mode = 'showing';

            // Log subtitle information
            console.log('Subtitle available:', subtitleTrack.src);
        }
    });
    </script>
</body>
</html>