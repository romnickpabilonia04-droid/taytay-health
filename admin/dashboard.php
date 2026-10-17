<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requireAdmin();

$pageTitle = "Admin Dashboard";

$users = 0;
$doctors = 0;
$departments = 0;
$appointments = 0;
$pending = 0;
$confirmed = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'patient'");
$users = (int) $result->fetch_assoc()["total"];

$result = $conn->query("SELECT COUNT(*) AS total FROM doctors");
$doctors = (int) $result->fetch_assoc()["total"];

$result = $conn->query("SELECT COUNT(*) AS total FROM departments");
$departments = (int) $result->fetch_assoc()["total"];

$result = $conn->query("SELECT COUNT(*) AS total FROM appointments");
$appointments = (int) $result->fetch_assoc()["total"];

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'Pending'
");
$pending = (int) $result->fetch_assoc()["total"];

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'Confirmed'
");
$confirmed = (int) $result->fetch_assoc()["total"];

require_once "../includes/header.php";
?>

<div class="dashboard-header">
    <div>
        <h1>Admin Dashboard</h1>
        <p class="welcome">
            Welcome,
            <?= htmlspecialchars($_SESSION["full_name"] ?? "Admin") ?>
            👋
        </p>
    </div>
</div>

<!-- STATISTICS -->
<div class="cards">

    <div class="card stat-card">
        <div class="stat-icon">👥</div>
        <div>
            <div class="stat-number"><?= $users ?></div>
            <div class="stat-label">Patients</div>
        </div>
    </div>

    <div class="card stat-card">
        <div class="stat-icon">👨‍⚕️</div>
        <div>
            <div class="stat-number"><?= $doctors ?></div>
            <div class="stat-label">Doctors</div>
        </div>
    </div>

    <div class="card stat-card">
        <div class="stat-icon">🏥</div>
        <div>
            <div class="stat-number"><?= $departments ?></div>
            <div class="stat-label">Departments</div>
        </div>
    </div>

    <div class="card stat-card">
        <div class="stat-icon">📅</div>
        <div>
            <div class="stat-number"><?= $appointments ?></div>
            <div class="stat-label">Appointments</div>
        </div>
    </div>

</div>

<!-- APPOINTMENT STATUS -->
<h2>Appointment Overview</h2>

<div class="grid">

    <div class="card">
        <div class="stat-icon">⏳</div>
        <h3>Pending Appointments</h3>
        <div class="stat-number"><?= $pending ?></div>
        <p class="welcome">
            Appointments waiting for admin action.
        </p>
    </div>

    <div class="card">
        <div class="stat-icon">✓</div>
        <h3>Confirmed Appointments</h3>
        <div class="stat-number"><?= $confirmed ?></div>
        <p class="welcome">
            Appointments already confirmed.
        </p>
    </div>

    <div class="card">
        <div class="stat-icon">⚙️</div>
        <h3>Manage Schedules</h3>
        <p class="welcome">
            Create doctor schedules and manage
            the 100 available appointment slots.
        </p>
        <a href="schedules.php" class="btn">
            Manage Schedules →
        </a>
    </div>

</div>

<!-- QUICK ACTIONS -->
<h2 style="margin-top:40px;">Quick Actions</h2>

<div class="grid">

    <div class="department-card">
        <div class="department-icon">🏥</div>
        <h3>Departments</h3>
        <p>Manage the medical departments.</p>
        <a href="departments.php" class="btn">
            Manage Departments
        </a>
    </div>

    <div class="department-card">
        <div class="department-icon">👨‍⚕️</div>
        <h3>Doctors</h3>
        <p>Add and manage doctors.</p>
        <a href="doctors.php" class="btn">
            Manage Doctors
        </a>
    </div>

    <div class="department-card">
        <div class="department-icon">📋</div>
        <h3>Appointments</h3>
        <p>Review and update patient appointments.</p>
        <a href="appointments.php" class="btn">
            Manage Appointments
        </a>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>