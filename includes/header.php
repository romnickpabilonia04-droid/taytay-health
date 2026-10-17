    <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? "Taytay Health";
$role = $_SESSION["role"] ?? "";

$currentFolder = basename(dirname($_SERVER["PHP_SELF"]));
$base = in_array($currentFolder, ["admin", "patient", "doctor"])
    ? "../"
    : "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <script src="<?= $base ?>assets/js/script.js" defer></script>
</head>
<body>

<div class="app-layout" id="appLayout">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="sidebar-logo">TH</span>
            <span class="sidebar-title">Taytay Health</span>
        </div>

        <div class="sidebar-menu">
            <p class="sidebar-label">MENU</p>

            <?php if ($role === "admin"): ?>
                <a class="sidebar-link" href="<?= $base ?>admin/dashboard.php"><span>▦</span> Dashboard</a>
                <a class="sidebar-link" href="<?= $base ?>admin/departments.php"><span>▤</span> Departments</a>
                <a class="sidebar-link" href="<?= $base ?>admin/doctors.php"><span>⚕</span> Doctors</a>
                <a class="sidebar-link" href="<?= $base ?>admin/schedules.php"><span>◷</span> Schedules</a>
                <a class="sidebar-link" href="<?= $base ?>admin/appointments.php"><span>▣</span> Appointments</a>
                <a class="sidebar-link" href="<?= $base ?>admin/notifications.php"><span>♧</span> Notifications</a>

            <?php elseif ($role === "doctor"): ?>
                <a class="sidebar-link" href="<?= $base ?>doctor/dashboard.php"><span>▦</span> Dashboard</a>
                <a class="sidebar-link" href="<?= $base ?>doctor/schedules.php"><span>◷</span> Schedules</a>

            <?php elseif ($role === "patient"): ?>
                <a class="sidebar-link" href="<?= $base ?>patient/dashboard.php"><span>▦</span> Dashboard</a>
                <a class="sidebar-link" href="<?= $base ?>patient/departments.php"><span>▤</span> Departments</a>
                <a class="sidebar-link" href="<?= $base ?>patient/doctors.php"><span>⚕</span> Doctors</a>
                <a class="sidebar-link" href="<?= $base ?>patient/appointments.php"><span>▣</span> My Appointments</a>
                <a class="sidebar-link" href="<?= $base ?>patient/history.php"><span>◴</span> History</a>
                <a class="sidebar-link" href="<?= $base ?>patient/notifications.php"><span>♧</span> Notifications</a>
                <a class="sidebar-link" href="<?= $base ?>patient/profile.php"><span>♙</span> Profile</a>

            <?php else: ?>
                <a class="sidebar-link" href="<?= $base ?>index.php"><span>⌂</span> Home</a>
                <a class="sidebar-link" href="<?= $base ?>login.php"><span>↪</span> Login</a>
                <a class="sidebar-link" href="<?= $base ?>register.php"><span>＋</span> Register</a>
            <?php endif; ?>
        </div>

        <div class="sidebar-bottom">
            <button type="button" class="theme-toggle" id="themeToggle">
                <span id="themeIcon">☾</span>
                <span id="themeText">Dark Mode</span>
            </button>

            <?php if (isset($_SESSION["user_id"])): ?>
                <a class="sidebar-link logout-link" href="<?= $base ?>logout.php">
                    <span>↪</span> Logout
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Close navigation" hidden></button>

    <div class="main-area">
        <header class="topbar">
            <button type="button"
                    class="sidebar-toggle"
                    id="sidebarToggle"
                    aria-label="Toggle sidebar"
                    aria-controls="sidebar"
                    aria-expanded="true">☰</button>

            <span class="topbar-title"><?= htmlspecialchars($pageTitle) ?></span>
        </header>

        <main class="container">