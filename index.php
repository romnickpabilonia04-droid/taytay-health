<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Taytay Health | Online Medical Appointment</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body>
<nav class="navbar">
    <div class="logo">Taytay Health</div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION["user_id"])): ?>
            <?php
            $dashboard = match ($_SESSION["role"]) {
                "admin" => "admin/dashboard.php",
                "doctor" => "doctor/dashboard.php",
                default => "patient/dashboard.php",
            };
            ?>
            <a href="<?= htmlspecialchars($dashboard) ?>">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Patient Registration</a>
        <?php endif; ?>
        <button type="button" class="theme-toggle theme-toggle-public" id="themeToggle" aria-pressed="false">
            <span id="themeIcon">☾</span>
            <span id="themeText">Dark Mode</span>
        </button>
    </div>
</nav>

<div class="container">
    <div class="card">
        <h1>Welcome to Taytay Health</h1>
        <h2>Online Medical Appointment System</h2>

        <p>
            Book medical appointments, choose a department,
            view available schedules, and track appointment updates
            through one convenient system.
        </p>

        <p>
            Available departments include Pediatrics, Obstetrics and
            Gynecology, Cardiology, Dermatology, and Internal Medicine.
        </p>

        <a href="login.php" class="btn">Login</a>
        <a href="register.php" class="btn btn-secondary">Register as Patient</a>
    </div>

    <h2>Our Departments</h2>

    <div class="grid">
        <div class="card">
            <h3>Pediatrics</h3>
            <p>Healthcare for infants, children, and adolescents.</p>
        </div>

        <div class="card">
            <h3>Obstetrics and Gynecology</h3>
            <p>Women's health and pregnancy-related care.</p>
        </div>

        <div class="card">
            <h3>Cardiology</h3>
            <p>Heart and cardiovascular health.</p>
        </div>

        <div class="card">
            <h3>Dermatology</h3>
            <p>Skin, hair, and nail health.</p>
        </div>

        <div class="card">
            <h3>Internal Medicine</h3>
            <p>General medical care for adults.</p>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; <?= date("Y") ?> Taytay Health. All rights reserved.</p>
</footer>
</body>
</html>