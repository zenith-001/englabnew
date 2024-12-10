<?php
include 'db.php';
session_start();

// ... (previous authentication code remains the same)

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes === false) return 'N/A';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < 4) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

// Fetch all movies with existence check
$stmt = $pdo->query("SELECT * FROM movies");
$movies = $stmt->fetchAll();

// Function to check file existence and get size
function checkFileDetails($path) {
    if (file_exists($path)) {
        $size = filesize($path);
        return [
            'exists' => true, 
            'size' => formatFileSize($size)
        ];
    }
    return [
        'exists' => false, 
        'size' => 'N/A'
    ];
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

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }

        .status-badge.exists {
            background-color: #4CAF50;
            color: white;
        }

        .status-badge.missing {
            background-color: #f44336;
            color: white;
        }

        .file-details {
            font-size: 0.8em;
            color: #999;
            margin-top: 5px;
        }

        td a {
            text-decoration: none;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #45a049;
        }

        button[style*="background-color: #f44336"] {
            background-color: #f44336;
        }

        button[style*="background-color: #f44336"]:hover {
            background-color: #d32f2f;
        }
        .file-details {
            font-size: 0.8em;
            color: #999;
            margin-top: 5px;
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
            <?php foreach ($movies as $movie): 
                $movieFileDetails = checkFileDetails($movie['file_path']);
                $subtitleFileDetails = $movie['subtitle_path'] ? checkFileDetails($movie['subtitle_path']) : ['exists' => false, 'size' => 'N/A'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($movie['name']); ?></td>
                    <td>
                        <?php 
                        echo htmlspecialchars($movie['file_path']) . '<br>';
                        echo $movieFileDetails['exists'] 
                            ? '<span class="status-badge exists">✓ Exists</span>' 
                            : '<span class="status-badge missing">✗ Missing</span>'; 
                        ?>
                        <div class="file-details">
                            Size: <?php echo $movieFileDetails['size']; ?>
                        </div>
                    </td>
                    <td>
                        <?php 
                        if ($movie['subtitle_path']) {
                            echo htmlspecialchars($movie['subtitle_path']) . '<br>';
                            echo $subtitleFileDetails['exists'] 
                                ? '<span class="status-badge exists">✓ Exists</span>' 
                                : '<span class="status-badge missing">✗ Missing</span>'; 
                        } else {
                            echo 'No subtitle';
                        }
                        ?>
                        <?php if ($movie['subtitle_path']): ?>
                            <div class="file-details">
                                Size: <?php echo $subtitleFileDetails['size']; ?>
                            </div>
                        <?php endif; ?>
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