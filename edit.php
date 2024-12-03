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
    
    // Fetch the file path associated with the movie
    $stmt = $pdo->prepare("SELECT file_path FROM movies WHERE id = ?");
    $stmt->execute([$delete_id]);
    $movie = $stmt->fetch();

    // Check if the movie exists
    if ($movie) {
        // Delete the associated file from the server
        if (file_exists($movie['file_path'])) {
            if (unlink($movie['file_path'])) {
                // File deleted successfully
                file_put_contents('error.log', "Successfully deleted file: " . $movie['file_path'] . "\n", FILE_APPEND);
            } else {
                // Log error if file deletion fails
                file_put_contents('error.log', "Failed to delete file: " . $movie['file_path'] . "\n", FILE_APPEND);
            }
        } else {
            file_put_contents('error.log', "File does not exist: " . $movie['file_path'] . "\n", FILE_APPEND);
        }
        
        // Now delete the movie record from the database
        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([$delete_id]);
    } else {
        file_put_contents('error.log', "Movie with ID $delete_id not found.\n", FILE_APPEND);
    }

    header('Location: edit.php'); // Redirect to the same page after deletion
    exit();
}

// Fetch all movies
$stmt = $pdo->query("SELECT * FROM movies");
$movies = $stmt->fetchAll();
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
        button {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .delete-button {
            background-color: #f44336;
        }
        .delete-button:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <h1>Edit Movies</h1>
    <table>
        <thead>
            <tr>
                <th>Movie Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movies as $movie): ?>
                <tr>
                    <td><?php echo htmlspecialchars($movie['name']); ?></td>
                    <td><?php echo htmlspecialchars($movie['description']); ?></td>
                    <td>
                        <a href="edit_movie.php?id=<?php echo $movie['id']; ?>"><button>Edit</button></a>
                        <a href="edit.php?delete_id=<?php echo $movie['id']; ?>" onclick="return confirm('Are you sure you want to delete this movie? This will also delete the associated file.');"><button class="delete-button">Delete</button></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>