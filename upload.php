<?php
include 'db.php';
session_start();

// Password protection
if (!isset ($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $file = $_FILES['movie_file'];

    // Handle file upload in chunks
    $upload_dir = 'uploads/';
    $chunk_size = 100 * 1024 * 1024; // 100 MB
    $total_size = $file['size'];
    $chunks = ceil($total_size / $chunk_size);
    $file_name = $upload_dir . basename($file['name']);

    // Open a file to write
    $out = fopen($file_name, 'wb');
    if ($out) {
        // Read the file in chunks
        $handle = fopen($file['tmp_name'], 'rb');
        if ($handle) {
            $uploaded = 0;
            while (!feof($handle)) {
                $buffer = fread($handle, $chunk_size);
                fwrite($out, $buffer);
                $uploaded += strlen($buffer);
                // Calculate progress
                $progress = ($uploaded / $total_size) * 100;
                file_put_contents('error.log', "Uploaded: " . round($progress, 2) . "%\n", FILE_APPEND);
            }
            fclose($handle);
        }
        fclose($out);
        // Save movie info to the database
        $stmt = $pdo->prepare("INSERT INTO movies (name, description, file) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $file_name]);
        file_put_contents('error.log', "Successfully uploaded: $name (ID: " . $pdo->lastInsertId() . ")\n", FILE_APPEND);
        header('Location: index.php');
        exit();
    } else {
        file_put_contents('error.log', "Failed to open output file: $file_name\n", FILE_APPEND);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Movie</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #ffffff;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #ffffff;
        }
        form {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            max-width: 600px;
            margin: auto;
        }
        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #2a2a2a;
            color: #ffffff;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        #progress-bar {
            width: 100%;
            background-color: #f3f3f3;
            border: 1px solid #ccc;
            margin-top: 20px;
        }
        #progress {
            width: 0;
            height: 30px;
            background-color: #4caf50;
            text-align: center;
            line-height: 30px;
            color: white;
        }
    </style>
</head>
<body>
    <h1>Upload Movie</h1>
    <form id="upload-form" method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Movie Name" required>
        <textarea name="description" placeholder="Movie Description" required></textarea>
        <input type="file" name="movie_file" required>
        <button type="submit"><i class="fas fa-upload"></i> Upload</button>
    </form>

    <div id="progress-bar">
        <div id="progress">0%</div>
    </div>

    <script>
        document.getElementById('upload-form').onsubmit = function(event) {
            event.preventDefault(); // Prevent default form submission

            var formData = new FormData(this);
            var xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    document.getElementById('progress').style.width = percentComplete + '%';
                    document.getElementById('progress').textContent = Math.round(percentComplete) + '%';
                }
            }, false);

            xhr.open('POST', 'upload.php', true);
            xhr.send(formData);
        };
    </script>
</body>
</html>