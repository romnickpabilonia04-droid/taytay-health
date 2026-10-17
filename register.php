<?php
require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $contact_number = trim($_POST["contact_number"]);

    if ($full_name === "" || $username === "" || $email === "" || $password === "") {
        $message = "Please fill in all required fields.";
    } else {

        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE username = ? OR email = ?"
        );
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Username or email already exists.";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users
                (username, password, full_name, email, contact_number, role)
                VALUES (?, ?, ?, ?, ?, 'patient')"
            );

            $stmt->bind_param(
                "sssss",
                $username,
                $hashedPassword,
                $full_name,
                $email,
                $contact_number
            );

            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $message = "Registration failed.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Registration</title>
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

    <h1>Patient Registration</h1>

    <?php if ($message): ?>
        <p class="error"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Full Name</label>
        <input type="text" name="full_name" required>

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Contact Number</label>
        <input type="text" name="contact_number">

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Register</button>

    </form>

    <p>
        Already have an account?
        <a href="login.php">Login</a>
    </p>

</div>

</body>
</html>