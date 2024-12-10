<?php
include 'db.php'; // This includes the database connection
session_start();

// Password protection
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the request is for the initial upload or a chunk upload
    if (isset($_POST['total_chunks'])) {
        $chunkIndex = $_POST['chunk_index'];
        $totalChunks = $_POST['total_chunks'];
        $movieId = $_POST['movie_id']; // Get the movie ID from the POST data

        // Handle chunk upload
        $file = $_FILES['movie_file'];
        $subtitleFile = $_FILES['subtitle_file']; // Handle subtitle file
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_file_name = $movieId . '.' . $file_extension;

        // Save the chunk to a temporary file
        $chunkPath = "uploads/" . $new_file_name . '.part' . $chunkIndex;
        move_uploaded_file($file['tmp_name'], $chunkPath);

        // Check if all chunks have been uploaded
        if ($chunkIndex + 1 == $totalChunks) {
            // Combine the uploaded chunks into a single file
            $combinedFilePath = "uploads/" . $new_file_name; // Path for the combined file
            $combinedFile = fopen($combinedFilePath, 'wb'); // Open the combined file for writing

            for ($i = 0; $i < $totalChunks; $i++) {
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

            // Handle subtitle file upload
            if ($subtitleFile['error'] === UPLOAD_ERR_OK) {
                $subtitle_extension = pathinfo($subtitleFile['name'], PATHINFO_EXTENSION);
                $subtitleFileName = $movieId . '.' . $subtitle_extension; // Name the subtitle file with the movie ID
                $subtitleFilePath = "uploads/" . $subtitleFileName;

                // Move the uploaded subtitle file
                move_uploaded_file($subtitleFile['tmp_name'], $subtitleFilePath);
            } else {
                file_put_contents('error.log', "Subtitle file upload error: " . $subtitleFile['error'] . "\n", FILE_APPEND);
            }

            // Update the database with the final file path
            try {
                $stmt = $pdo->prepare("UPDATE movies SET file_path = ?, subtitle_path = ? WHERE id = ?");
                $stmt->execute([$combinedFilePath, $subtitleFilePath ?? null, $movieId]);
            } catch (PDOException $e) {
                file_put_contents('error.log', "Database update error: " . $e->getMessage() . "\n", FILE_APPEND);
                die("Database update error.");
            }

            echo "File uploaded successfully!";
        } else {
            echo "Chunk uploaded successfully!";
        }
        exit();
    } else {
        // Initial upload request
        $name = $_POST['name'];
        $description = $_POST['description'];

        // Insert movie details into the database first to get the ID
        try {
            $stmt = $pdo->prepare("INSERT INTO movies (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $movieId = $pdo->lastInsertId(); // Get the last inserted ID
            echo json_encode(['movie_id' => $movieId]); // Return the movie ID for chunk uploads
            exit();
        } catch (PDOException $e) {
            file_put_contents('error.log', "Database insert error: " . $e->getMessage() . "\n", FILE_APPEND);
            die("Database insert error.");
        }
    }
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
        <input type="file" name="subtitle_file" required> <!-- New subtitle file input -->
        <button type="submit">Upload Movie</button>
        <div class="progress">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
    </form>

    <script>
        const form = document.getElementById('upload-form');
        const progressBar = document.getElementById('progress-bar');

        form.addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent default form submission

            const fileInput = document.querySelector('input[name="movie_file"]');
            const subtitleInput = document.querySelector('input[name="subtitle_file"]');
            const file = fileInput.files[0];
            const chunkSize = 100 * 1024 * 1024; // 100MB
            const totalChunks = Math.ceil(file.size / chunkSize);
            let currentChunk = 0;

            // Initial upload to get movie ID
            const initialFormData = new FormData();
            initialFormData.append('name', document.querySelector('input[name="name"]').value);
            initialFormData.append('description', document.querySelector('input[name="description"]').value);
            initialFormData.append('subtitle_file', subtitleInput.files[0]); // Include subtitle file

            const xhrInitial = new XMLHttpRequest();
            xhrInitial.open('POST', 'upload.php', true);
            xhrInitial.onload = function () {
                if (xhrInitial.status === 200) {
                    const response = JSON.parse(xhrInitial.responseText);
                    const movieId = response.movie_id; // Get the movie ID for chunk uploads

                    function uploadChunk() {
                        const start = currentChunk * chunkSize;
                        const end = Math.min(start + chunkSize, file.size);
                        const chunk = file.slice(start, end);
                        const formData = new FormData();
                        formData.append('movie_file', chunk, file.name);
                        formData.append('chunk_index', currentChunk);
                        formData.append('total_chunks', totalChunks);
                        formData.append('movie_id', movieId); // Include movie ID

                        const xhrChunk = new XMLHttpRequest();
                        xhrChunk.open('POST', 'upload.php', true);

                        xhrChunk.upload.addEventListener('progress', function (e) {
                            if (e.lengthComputable) {
                                const percentComplete = ((currentChunk * chunkSize + e.loaded) / file.size) * 100;
                                progressBar.style.width = percentComplete + '%';
                            }
                        });
                        xhrChunk.onload = function () {
                            if (xhrChunk.status === 200) {
                                currentChunk++;
                                if (currentChunk < totalChunks) {
                                    uploadChunk(); // continue uploading the next chunk
                                } else {
                                    progressBar.style.width = '100%'; // Complete progress
                                    alert('Upload completed successfully!');
                                }
                            } else {
                                alert('Error uploading chunk: ' + xhrChunk.responseText);
                            }
                        };

                        xhrChunk.send(formData);
                    }

                    uploadChunk(); // Start uploading the first chunk
                } else {
                    alert('Error starting upload: ' + xhrInitial.responseText);
                }
            };

            xhrInitial.send(initialFormData); // Send initial data to get movie ID
        });
    </script>
</body>

</html>