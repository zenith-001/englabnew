<?php
include 'db.php';
session_start();

// Password protection
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Function to generate a unique file name
function generateUniqueFileName($originalName, $movieId, $type)
{
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return $movieId . '_' . $type . '.' . $extension;
}

// Function to convert subtitles to WebVTT
// Function to convert subtitles to WebVTT
function convertToWebVTT($inputFile, $outputFile)
{
    $extension = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));

    if ($extension === 'srt') {
        // Use FFmpeg to convert SRT to WebVTT
        $command = "ffmpeg -i " . escapeshellarg($inputFile) . " " . escapeshellarg($outputFile);
        
        // Execute the command and capture output
        exec($command . ' 2>&1', $output, $returnVar);

        // Check if the conversion was successful
        if ($returnVar !== 0) {
            // Log the command output for debugging
            file_put_contents('error.log', "FFmpeg conversion error: " . implode("\n", $output) . "\nCommand: $command\n", FILE_APPEND);
            return false;
        }

        return true;
    } elseif ($extension === 'ass' || $extension === 'ssa') {
        // Handle ASS/SSA conversion if needed
        // (You can keep your existing code for ASS/SSA here)
    } else {
        file_put_contents('error.log', "Unsupported subtitle format: $extension\n", FILE_APPEND);
        return false;
    }
}
// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Initial upload request to get movie details
        if (isset($_POST['action']) && $_POST['action'] === 'init') {
            $name = $_POST['name'];
            $description = $_POST['description'];

            // Insert movie details into the database
            $stmt = $pdo->prepare("INSERT INTO movies (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $movieId = $pdo->lastInsertId();

            echo json_encode([
                'status' => 'success',
                'movie_id' => $movieId
            ]);
            exit();
        }

        // Chunk upload handling
        if (isset($_POST['action']) && $_POST['action'] === 'upload_chunk') {
            $movieId = $_POST['movie_id'];
            $chunkIndex = $_POST['chunk_index'];
            $totalChunks = $_POST['total_chunks'];

            // Movie file upload
            if (isset($_FILES['movie_file'])) {
                $movieFile = $_FILES['movie_file'];
                $movieFileExtension = pathinfo($movieFile['name'], PATHINFO_EXTENSION);
                $movieFileName = generateUniqueFileName($movieFile['name'], $movieId, 'movie');
                $movieFilePath = "uploads/" . $movieFileName;
                $chunkPath = $movieFilePath . '.part' . $chunkIndex;

                // Ensure uploads directory exists
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }

                // Save chunk
                move_uploaded_file($movieFile['tmp_name'], $chunkPath);

                // Check if all chunks are uploaded
                if ($chunkIndex + 1 == $totalChunks) {
                    // Combine chunks
                    $finalFile = fopen($movieFilePath, 'wb');
                    for ($i = 0; $i < $totalChunks; $i++) {
                        $currentChunkPath = $movieFilePath . '.part' . $i;
                        if (file_exists($currentChunkPath)) {
                            $chunkContent = file_get_contents($currentChunkPath);
                            fwrite($finalFile, $chunkContent);
                            unlink($currentChunkPath);
                        }
                    }
                    fclose($finalFile);

                    // Update database with file path
                    $stmt = $pdo->prepare("UPDATE movies SET file_path = ? WHERE id = ?");
                    $stmt->execute([$movieFilePath, $movieId]);
                }
            }

            // Subtitle file upload
            if (isset($_FILES['subtitle_file']) && $_FILES['subtitle_file']['error'] === UPLOAD_ERR_OK) {
                $subtitleFile = $_FILES['subtitle_file'];
                $subtitleExtension = pathinfo($subtitleFile['name'], PATHINFO_EXTENSION);
                $subtitleFileName = generateUniqueFileName($subtitleFile['name'], $movieId, 'subtitle');
                $subtitleFilePath = "uploads/" . $subtitleFileName;
                $subtitleWebVTTPath = "uploads/" . $movieId . '_subtitle.vtt';

                // Move original subtitle file
                move_uploaded_file($subtitleFile['tmp_name'], $subtitleFilePath);

                // Convert to WebVTT
                if (convertToWebVTT($subtitleFilePath, $subtitleWebVTTPath)) {
                    // Update database with subtitle path
                    $stmt = $pdo->prepare("UPDATE movies SET subtitle_path = ? WHERE id = ?");
                    $stmt->execute([$subtitleWebVTTPath, $movieId]);

                    // Optional: Remove original subtitle file
                    unlink($subtitleFilePath);
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Chunk uploaded successfully'
            ]);
            exit();
        }
    } catch (Exception $e) {
        file_put_contents('error.log', "Upload error: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Movie Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .upload-form {
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
        }

        input,
        button {
            width: 100%;
            margin: 10px 0;
            padding: 10px;
            box-sizing: border-box;
        }

        .progress-container {
            background-color: #333;
            border-radius: 5px;
            margin-top: 20px;
        }

        .progress-bar {
            width: 0%;
            height: 20px;
            background-color: #4CAF50;
            border-radius: 5px;
            transition: width 0.5s;
        }
    </style>
</head>

<body>
    <div class="upload-form">
        <h2>Upload Movie</h2>
        <form id="uploadForm">
            <input type="text" name="name" placeholder="Movie Name" required>
            <input type="text" name="description" placeholder="Movie Description" required>
            <input type="file" name="movie_file" accept="video/*" required>
            <input type="file" name="subtitle_file" accept=".srt,.ass,.ssa,.vtt" required>
            <button type="submit">Upload Movie</button>
        </form>
        <div class="progress-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>
    </div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const movieFile = form.querySelector('input[name="movie_file"]').files[0];
            const subtitleFile = form.querySelector('input[name="subtitle_file"]').files[0];
            const progressBar = document.getElementById('progressBar');

            // Chunk upload configuration
            const CHUNK_SIZE = 10 * 1024 * 1024; // 10MB chunks
            const totalChunks = Math.ceil(movieFile.size / CHUNK_SIZE);

            // Initial upload to get movie ID
            async function initiateUpload() {
                const initialData = new FormData();
                initialData.append('action', 'init');
                initialData.append('name', form.querySelector('input[name="name"]').value);
                initialData.append('description', form.querySelector('input[name="description"]').value);

                try {
                    const response = await fetch('upload.php', {
                        method: 'POST',
                        body: initialData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        return result.movie_id;
                    } else {
                        throw new Error('Failed to initiate upload');
                    }
                } catch (error) {
                    console.error('Initialization error:', error);
                    alert('Upload initialization failed');
                    return null;
                }
            }

            // Chunk upload function
            async function uploadChunks(movieId) {
                for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                    const start = chunkIndex * CHUNK_SIZE;
                    const end = Math.min(start + CHUNK_SIZE, movieFile.size);
                    const chunk = movieFile.slice(start, end);

                    const chunkData = new FormData();
                    chunkData.append('action', 'upload_chunk');
                    chunkData.append('movie_id', movieId);
                    chunkData.append('movie_file', chunk, movieFile.name);
                    chunkData.append('subtitle_file', subtitleFile);
                    chunkData.append('chunk_index', chunkIndex);
                    chunkData.append('total_chunks', totalChunks);

                    try {
                        const response = await fetch('upload.php', {
                            method: 'POST',
                            body: chunkData
                        });
                        const result = await response.json();

                        // Update progress bar
                        const progress = ((chunkIndex + 1) / totalChunks) * 100;
                        progressBar.style.width = `${progress}%`;

                        if (result.status !== 'success') {
                            throw new Error('Chunk upload failed');
                        }
                    } catch (error) {
                        console.error('Chunk upload error:', error);
                        alert('Upload failed');
                        return false;
                    }
                }
                return true;
            }

            // Main upload process
            async function startUpload() {
                const movieId = await initiateUpload();
                if (movieId) {
                    const uploadSuccess = await uploadChunks(movieId);
                    if (uploadSuccess) {
                        alert('Movie uploaded successfully!');
                        form.reset();
                        progressBar.style.width = '0%';
                    }
                }
            }

            // Start the upload process
            startUpload();
        });
    </script>
</body>

</html>