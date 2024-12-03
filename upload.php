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

    // Check for file upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        file_put_contents('error.log', "File upload error: " . $file['error'] . "\n", FILE_APPEND);
        die("File upload error.");
    }

    // Insert movie details into the database first to get the ID
    $stmt = $pdo->prepare("INSERT INTO movies (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    $movieId = $pdo->lastInsertId(); // Get the last inserted ID

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
    // Upload the file to the FTP server
    if (ftp_put($ftp_conn, "uploads/" . $new_file_name, $local_file_path, FTP_BINARY)) {
        // Update the database with the new file path
        $file_path = "uploads/" . $new_file_name; // Path on the FTP server
        $stmt = $pdo->prepare("UPDATE movies SET 'file' = ? WHERE id = ?");

        // Execute the statement and check for errors
        if ($stmt->execute([$file_path, $movieId])) {
            // Log successful upload
            file_put_contents('error.log', "Successfully uploaded movie: $name (ID: $movieId) with file path: $file_path\n", FILE_APPEND);

            header('Location: upload.php?success=true');
            exit();
        } else {
            // Log error if the update fails
            file_put_contents('error.log', "Failed to update database with file path for movie ID: $movieId\n", FILE_APPEND);
        }
    } else {
        file_put_contents('error.log', "Failed to upload file: $new_file_name\n", FILE_APPEND);
    }

    // Close FTP connection
    ftp_close($ftp_conn);
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

        input[type="text"],
        textarea,
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
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Movie Name" required>
        <textarea name="description" placeholder="Movie Description" required></textarea>
        <input type="file" name="movie_file" required>
        <button type="submit">Upload Movie</button>
    </form>
    <div id="progress-bar">
        <div id="progress">0%</div>
    </div>

    <script>
        const form = document.querySelector('form');
        const progressBar = document.getElementById('progress');

        form.addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent the default form submission

            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            xhr.open('POST', form.action, true);

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                    progressBar.textContent = Math.round(percentComplete) + '%';
                }
            });

            xhr.onload = function () {
                if (xhr.status === 200) {
                    alert('Upload successful!');
                    window.location.reload(); // Reload the page to see the new upload
                } else {
                    alert('Upload failed. Please try again.');
                }
            };

            xhr.send(formData);
        });
    </script>
</body>

</html>