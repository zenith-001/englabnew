<?php
include 'db.php'; // This includes the database connection
session_start();

// Password protection
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $file = $_FILES['movie_file'];
    file_put_contents('error.log', print_r($_FILES, true), FILE_APPEND);
    // Check for file upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $file['error'];
        file_put_contents('error.log', "File upload error: " . $errorCode . "\n", FILE_APPEND);
        die("File upload error.");
    }

    // Insert movie details into the database first to get the ID
    try {
        $stmt = $pdo->prepare("INSERT INTO movies (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        $movieId = $pdo->lastInsertId(); // Get the last inserted ID
    } catch (PDOException $e) {
        file_put_contents('error.log', "Database insert error: " . $e->getMessage() . "\n", FILE_APPEND);
        die("Database insert error.");
    }

    // Define the new file name using the movie ID
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_file_name = $movieId . '.' . $file_extension;
    $local_file_path = $file['tmp_name']; // Temporary file path on the server

    // Establish FTP connection
    $ftp_conn = ftp_connect($ftp_server);
    if (!$ftp_conn) {
        file_put_contents('error.log', "Could not connect to FTP server.\n", FILE_APPEND);
        die("Could not connect to FTP server.");
    }

    // Log in to FTP server
    if (!ftp_login($ftp_conn, $ftp_user, $ftp_pass)) {
        ftp_close($ftp_conn);
        file_put_contents('error.log', "Could not log in to FTP server.\n", FILE_APPEND);
        die("Could not log in to FTP server.");
    }

    // Chunk size of 100MB
    $chunkSize = 100 * 1024 * 1024; // 100MB
    $fileSize = $file['size'];
    $chunkCount = ceil($fileSize / $chunkSize);

    // Upload each chunk
    for ($i = 0; $i < $chunkCount; $i++) {
        $chunkPath = $local_file_path . '.part' . $i; // Create a temporary chunk file
        $handle = fopen($local_file_path, 'rb');
        fseek($handle, $i * $chunkSize); // Move the pointer to the correct chunk position
        $chunkData = fread($handle, $chunkSize); // Read the chunk
        fclose($handle);

        // Write the chunk to a temporary file
        file_put_contents($chunkPath, $chunkData);

        // Upload the chunk to the FTP server
        if (ftp_put($ftp_conn, "uploads/" . $new_file_name . '.part' . $i, $chunkPath, FTP_BINARY)) {
            file_put_contents('error.log', "Successfully uploaded chunk $i for movie ID: $movieId\n", FILE_APPEND);
            unlink($chunkPath); // Delete the temporary chunk file after upload
        } else {
            file_put_contents('error.log', "Failed to upload chunk $i for movie ID: $movieId\n", FILE_APPEND);
            die("Failed to upload chunk $i.");
        }
    }

    // Combine the uploaded chunks into a single file
    $combinedFilePath = "uploads/" . $new_file_name; // Path for the combined file
    $combinedFile = fopen($combinedFilePath, 'wb'); // Open the combined file for writing

    for ($i = 0; $i < $chunkCount; $i++) {
        $chunkPath = "uploads/" . $new_file_name . '.part' . $i; // Path of the chunk file
        if (file_exists($chunkPath)) {
            $chunkData = file_get_contents($chunkPath); // Read the chunk data
            fwrite($combinedFile, $chunkData); // Write the chunk data to the combined file
            unlink($chunkPath); // Delete the chunk file after combining
        } else {
            file_put_contents('error.log', "Chunk file $chunkPath does not exist.\n", FILE_APPEND);
            die("Chunk file $chunkPath does not exist.");
        }
    }

    fclose($combinedFile); // Close the combined file

    // Update the database with the final file path
    try {
        $stmt = $pdo->prepare("UPDATE movies SET file_path = ? WHERE id = ?");
        $stmt->execute([$combinedFilePath, $movieId]);
    } catch (PDOException $e) {
        file_put_contents('error.log', "Database update error: " . $e->getMessage() . "\n", FILE_APPEND);
        die("Database update error.");
    }

    ftp_close($ftp_conn); // Close the FTP connection
    echo "File uploaded successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Upload Movie</title>
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

        form {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            max-width: 600px;
            margin: auto;
        }

        input[type="text"],
        input[type="file"] {
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

        .progress {
            width: 100%;
            background-color: #444;
            border-radius: 5px;
            margin-top: 10px;
        }

        .progress-bar {
            height: 20px;
            background-color: #4CAF50;
            width: 0%;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <h1>Upload Movie</h1>
    <form method="POST" enctype="multipart/form-data" id="upload-form">
        <input type="text" name="name" placeholder="Movie Name" required>
        <input type="text" name="description" placeholder="Movie Description" required>
        <input type="file" name="movie_file" required>
        <button type="submit">Upload Movie</button>
        <div class="progress">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
    </form>

    <script>
        // JavaScript for progress bar
        const form = document.getElementById('upload-form');
        const progressBar = document.getElementById('progress-bar');

        form.addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            xhr.open('POST', 'upload.php', true);

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                }
            });
            console.log(formData);
            xhr.onload = function () {
            console.log("Loded:",formData);
                if (xhr.status === 200) {
                    alert('Upload successful!');
                } else {
                    alert('Upload failed.');
                }
            };

            xhr.send(formData);
        });
    </script>
</body>

</html>