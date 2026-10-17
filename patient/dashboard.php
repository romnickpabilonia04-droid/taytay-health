<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requirePatient();

$user_id = $_SESSION["user_id"];
$full_name = $_SESSION["full_name"];

$total = 0;
$pending = 0;
$confirmed = 0;
$completed = 0;

$stmt = $conn->prepare("
    SELECT status, COUNT(*) AS total
    FROM appointments
    WHERE user_id = ?
    GROUP BY status
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $total += $row["total"];

    if ($row["status"] === "Pending") {
        $pending = $row["total"];
    }

    if ($row["status"] === "Confirmed") {
        $confirmed = $row["total"];
    }

    if ($row["status"] === "Completed") {
        $completed = $row["total"];
    }
}

$stmt = $conn->prepare("
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.slot_number,
        a.status,
        d.doctor_name,
        dep.department_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN departments dep ON d.department_id = dep.department_id
    WHERE a.user_id = ?
    AND a.status IN ('Pending', 'Confirmed')
    ORDER BY a.appointment_date ASC
    LIMIT 5
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$appointments = $stmt->get_result();
?>
<?php
$pageTitle = "Patient Dashboard";
require_once "../includes/header.php";
?>

    <div class="dashboard-header">

        <div>
            <h1>Welcome, <?= htmlspecialchars($full_name) ?> 👋</h1>

            <p class="welcome">
                Manage your medical appointments easily.
            </p>
        </div>

        <a href="departments.php" class="btn">
            + Book Appointment
        </a>

    </div>


    <!-- STATISTICS -->

    <div class="cards">

        <div class="card stat-card">

            <div class="stat-icon">📅</div>

            <div>
                <div class="stat-number">
                    <?= $total ?>
                </div>

                <div class="stat-label">
                    Total Appointments
                </div>
            </div>

        </div>


        <div class="card stat-card">

            <div class="stat-icon">⏳</div>

            <div>
                <div class="stat-number">
                    <?= $pending ?>
                </div>

                <div class="stat-label">
                    Pending
                </div>
            </div>

        </div>


        <div class="card stat-card">

            <div class="stat-icon">✓</div>

            <div>
                <div class="stat-number">
                    <?= $confirmed ?>
                </div>

                <div class="stat-label">
                    Confirmed
                </div>
            </div>

        </div>


        <div class="card stat-card">

            <div class="stat-icon">🏥</div>

            <div>
                <div class="stat-number">
                    <?= $completed ?>
                </div>

                <div class="stat-label">
                    Completed
                </div>
            </div>

        </div>

    </div>


    <!-- QUICK ACTIONS -->

    <h2>Quick Actions</h2>

    <div class="grid">

        <div class="department-card">

            <div class="department-icon">🏥</div>

            <h3>Book Appointment</h3>

            <p>
                Choose a department, doctor, and available schedule.
            </p>

            <a href="departments.php" class="btn">
                Book Now
            </a>

        </div>


        <div class="department-card">

            <div class="department-icon">📋</div>

            <h3>My Appointments</h3>

            <p>
                View your upcoming and previous appointments.
            </p>

            <a href="appointments.php" class="btn">
                View Appointments
            </a>

        </div>


        <div class="department-card">

            <div class="department-icon">🔔</div>

            <h3>Notifications</h3>

            <p>
                Check updates about your appointment.
            </p>

            <a href="notifications.php" class="btn">
                View Notifications
            </a>

        </div>

    </div>


    <!-- UPCOMING APPOINTMENTS -->

    <div style="margin-top: 40px;">

        <div class="dashboard-header">

            <div>
                <h2>Upcoming Appointments</h2>

                <p class="welcome">
                    Your latest scheduled appointments.
                </p>
            </div>

            <a href="appointments.php">
                View All →
            </a>

        </div>


        <?php if ($appointments->num_rows > 0): ?>

            <?php while ($appointment = $appointments->fetch_assoc()): ?>

                <div class="appointment-card">

                    <div class="appointment-header">

                        <div>

                            <h3>
                                <?= htmlspecialchars($appointment["department_name"]) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($appointment["doctor_name"]) ?>
                            </p>

                        </div>

                        <span class="status
                            status-<?= strtolower($appointment["status"]) ?>">

                            <?= htmlspecialchars($appointment["status"]) ?>

                        </span>

                    </div>


                    <p style="margin-top: 15px;">

                        📅
                        <?= date(
                            "F d, Y",
                            strtotime($appointment["appointment_date"])
                        ) ?>

                        &nbsp;&nbsp;

                        🎫 Slot #<?= $appointment["slot_number"] ?>

                    </p>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="card">

                <h3>No upcoming appointments</h3>

                <p class="welcome">
                    You don't have any upcoming appointments yet.
                </p>

                <a href="departments.php" class="btn">
                    Book Your First Appointment
                </a>

            </div>

        <?php endif; ?>

    </div>

<?php require_once "../includes/footer.php"; ?>