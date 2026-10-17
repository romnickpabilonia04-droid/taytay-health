<?php
session_start();
require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare(
        "SELECT user_id, username, password, full_name, role
         FROM users
         WHERE username = ?"
    );

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "admin") {
            header("Location: admin/dashboard.php");
        } elseif ($user["role"] === "doctor") {
            header("Location: doctor/dashboard.php");
        } else {
            header("Location: patient/dashboard.php");
        }

        exit;

    } else {
        $message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>

<body>

<nav class="navbar public-theme-bar">
    <div class="logo">Taytay Health</div>
    <button type="button" class="theme-toggle theme-toggle-public" id="themeToggle" aria-pressed="false">
        <span id="themeIcon">☾</span>
        <span id="themeText">Dark Mode</span>
    </button>
</nav>

<div class="container">

    <h1>Online Medical Appointment System</h1>
    <h2>Login</h2>

    <?php if (isset($_GET["registered"])): ?>
        <p class="success">Registration successful. Please login.</p>
    <?php endif; ?>

    <?php if ($message): ?>
        <p class="error"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>

    </form>

    <p>
        No account yet?
        <a href="register.php">Register</a>
    </p>

</div>

</body>
</html>