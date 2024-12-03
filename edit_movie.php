<?php
include 'db.php';
session_start();

// Check if the user is logged in (optional)
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Fetch movie details based on ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    $movie = $stmt->fetch();

    // Check if the movie exists
    if (!$movie) {
        echo "Movie not found.";
        exit();
    }
} else {
    echo "No movie specified.";
    exit();
}

// Update movie details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE movies SET name = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $description, $id]);

    // Redirect to edit.php after updating
    header('Location: edit.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Movie</title>
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
        input[type="text"], textarea {
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
    </style>
</head>
<body>
    <h1>Edit Movie</h1>
    <form method="POST">
        <input type="text" name="name" placeholder="Movie Name" value="<?php echo htmlspecialchars($movie['name']); ?>" required>
        <textarea name="description" placeholder="Movie Description" required><?php echo htmlspecialchars($movie['description']); ?></textarea>
        <button type="submit">Update Movie</button>
    </form>
</body>
</html>