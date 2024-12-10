<?php
include 'db.php';
session_start();

// Check if the user is logged in (optional)
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Check if movie entry exists in database
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$delete_id]);
    $movie = $stmt->fetch();

    $deletion_status = [
        'database_entry' => false,
        'file_exists' => false,
        'subtitle_exists' => false,
        'deletion_successful' => false
    ];

    if (!$movie) {
        $deletion_status['database_entry'] = false;
        file_put_contents('error.log', "Movie entry with ID $delete_id does not exist in database.\n", FILE_APPEND);
    } else {
        $deletion_status['database_entry'] = true;
        
        // Check file existence
        $file_path = $movie['file_path'];
        $subtitle_path = $movie['subtitle_path'];
        
        $deletion_status['file_exists'] = file_exists($file_path);
        $deletion_status['subtitle_exists'] = $subtitle_path ? file_exists($subtitle_path) : false;

        // Proceed with deletion if you want
        try {
            // Delete file if it exists
            if ($deletion_status['file_exists']) {
                unlink($file_path);
            }

            // Delete subtitle if it exists
            if ($deletion_status['subtitle_exists']) {
                unlink($subtitle_path);
            }

            // Delete database entry
            $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
            $stmt->execute([$delete_id]);

            $deletion_status['deletion_successful'] = true;
            
            file_put_contents('error.log', "Movie ID $delete_id deleted successfully.\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents('error.log', "Deletion error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

// Fetch all movies with existence check
$stmt = $pdo->query("SELECT * FROM movies");
$movies = $stmt->fetchAll();

// Function to check file existence
function checkFileExistence($path) {
    return file_exists($path) ? 
        '<span style="color:green;">✓ Exists</span>' : 
        '<span style="color:red;">✗ Missing</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Movies</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #ffffff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #444;
        }
        th {
            background-color: #1e1e1e;
        }
        tr:nth-child(even) {
            background-color: #2a2a2a;
        }
        .status-badge {
            padding: 5px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        .exists {
            background-color: #4CAF50;
            color: white;
        }
        .missing {
            background-color: #f44336;
            color: white;
        }
        .deletion-status {
            margin: 20px 0;
            padding: 10px;
            background-color: #1e1e1e;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Edit Movies</h1>

    <table>
        <thead>
            <tr>
                <th>Movie Name</th>
                <th>Movie File</th>
                <th>Subtitle File</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movies as $movie): ?>
                <tr>
                    <td><?php echo htmlspecialchars($movie['name']); ?></td>
                    <td>
                        <?php 
                        echo htmlspecialchars($movie['file_path']) . '<br>';
                        echo checkFileExistence($movie['file_path']); 
                        ?>
                    </td>
                    <td>
                        <?php 
                        echo $movie['subtitle_path'] ? 
                            (htmlspecialchars($movie['subtitle_path']) . '<br>' . 
                             checkFileExistence($movie['subtitle_path'])) : 
                            'No subtitle'; 
                        ?>
                    </td>
                    <td>
                        <a href="edit_movie.php?id=<?php echo $movie['id']; ?>">
                            <button>Edit</button>
                        </a>
                        <a href="edit.php?delete_id=<?php echo $movie['id']; ?>" 
                           onclick="return confirm('Are you sure you want to delete this movie? This will delete the movie entry and associated files.');">
                            <button style="background-color: #f44336;">Delete</button>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>