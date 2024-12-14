<?php
include 'db.php';
session_start();

// Password protection
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes === null || $bytes === 0) {
        return 'N/A';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    return number_format($bytes / (1024 ** $power), 2, '.', ',') . ' ' . $units[$power];
}

// Function to safely delete files
function safeDeleteFile($filePath) {
    try {
        if (!empty($filePath) && file_exists($filePath)) {
            if (unlink($filePath)) {
                return true;
            } else {
                // Log file deletion failure
                file_put_contents('error.log', "Failed to delete file: $filePath\n", FILE_APPEND);
                return false;
            }
        }
        return true; // Return true if file doesn't exist
    } catch (Exception $e) {
        // Log any exceptions during file deletion
        file_put_contents('error.log', "Exception deleting file: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

// Handle movie deletion
if (isset($_GET['delete_id'])) {
    try {
        // Start a database transaction for safer deletion
        $pdo->beginTransaction();

        // Fetch the movie details to get the file paths
        $deleteId = $_GET['delete_id'];
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$deleteId]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$movie) {
            // Log if movie not found
            file_put_contents('error.log', "Attempted to delete non-existent movie ID: $deleteId\n", FILE_APPEND);
            throw new Exception("Movie not found");
        }

        // Attempt to delete associated files
        $movieFileDeleted = safeDeleteFile($movie['file_path']);
        $subtitleFileDeleted = safeDeleteFile($movie['subtitle_path']);

        // Delete the database entry
        $deleteStmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
        $deleteResult = $deleteStmt->execute([$deleteId]);

        if (!$deleteResult) {
            throw new Exception("Failed to delete movie from database");
        }

        // Commit the transaction
        $pdo->commit();

        // Log successful deletion
        file_put_contents('error.log', "Successfully deleted movie ID: $deleteId\n", FILE_APPEND);

        // Redirect with success message
        header('Location: edit.php?delete_success=1');
        exit();

    } catch (Exception $e) {
        // Rollback the transaction in case of any error
        $pdo->rollBack();

        // Log the full error
        file_put_contents('error.log', "Deletion error: " . $e->getMessage() . "\n", FILE_APPEND);

        // Redirect with error message
        header('Location: edit.php?delete_error=1');
        exit();
    }
}

// Fetch all movies
try {
    $stmt = $pdo->query("SELECT * FROM movies");
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Log database query error
    file_put_contents('error.log', "Database query error: " . $e->getMessage() . "\n", FILE_APPEND);
    $movies = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Movies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .alert-success {
            background-color: #4CAF50;
            color: white;
        }

        .alert-error {
            background-color: #f44336;
            color: white;
        }

        h1 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #1e1e1e;
            border-radius: 8px;
            overflow: hidden;
        }

        table, th, td {
            border: 1px solid #444;
        }

        th {
            background-color: #2a2a2a;
            color: #ffffff;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            vertical-align: middle;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-edit {
            background-color: #4CAF50;
            color: white;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .file-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-top: 5px;
        }

        .file-status-exists {
            background-color: #4CAF50;
            color: white;
        }

        .file-status-missing {
            background-color: #f44336;
            color: white;
        }

        .file-details {
            font-size: 0.9em;
            color: #aaa;
        }
    </style>
</head>
<body>
    <h1>Edit Movies</h1>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['delete_success'])): ?>
        <div class="alert alert-success">
            Movie deleted successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['delete_error'])): ?>
        <div class="alert alert-error">
            Error deleting movie. Please check the error log.
        </div>
    <?php endif; ?>

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
                        $movieFileExists = file_exists($movie['file_path']);
                        $movieFileSize = $movieFileExists ? filesize($movie['file_path']) : null;
                        ?>
                        <div>
                            <?php echo htmlspecialchars($movie['file_path']); ?>
                        </div>
                        <div class="file-details">
                            <?php 
                 echo $movieFileExists 
                                ? '<span class="file-status file-status-exists">✓ Exists</span> - Size: ' . formatFileSize($movieFileSize) 
                                : '<span class="file-status file-status-missing">✗ Missing</span>'; 
                            ?>
                        </div>
                    </td>
                    <td>
                        <?php 
                        if ($movie['subtitle_path']) {
                            $subtitleFileExists = file_exists($movie['subtitle_path']);
                            $subtitleFileSize = $subtitleFileExists ? filesize($movie['subtitle_path']) : null;
                            ?>
                            <div>
                                <?php echo htmlspecialchars($movie['subtitle_path']); ?>
                            </div>
                            <div class="file-details">
                                <?php 
                                echo $subtitleFileExists 
                                    ? '<span class="file-status file-status-exists">✓ Exists</span> - Size: ' . formatFileSize($subtitleFileSize) 
                                    : '<span class="file-status file-status-missing">✗ Missing</span>'; 
                                ?>
                            </div>
                        <?php } else {
                            echo 'No subtitle';
                        }
                        ?>
                    </td>
                    <td class="actions">
                        <a href="edit_movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-edit">Edit</a>
                        <a href="edit.php?delete_id=<?php echo $movie['id']; ?>" 
                           onclick="return confirm('Are you sure you want to delete this movie? This will delete the movie entry and associated files.');" 
                           class="btn btn-delete">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>