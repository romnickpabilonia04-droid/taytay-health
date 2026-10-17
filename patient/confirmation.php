<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requirePatient();

$appointment_id = isset($_GET["appointment_id"])
    ? (int) $_GET["appointment_id"]
    : 0;

if ($appointment_id <= 0) {
    header("Location: appointments.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT
        a.appointment_id,
        a.appointment_date,
        a.slot_number,
        a.status,
        a.created_at,

        d.doctor_name,
        d.specialization,

        dep.department_name,

        s.start_time,
        s.end_time

    FROM appointments a

    JOIN doctors d
        ON a.doctor_id = d.doctor_id

    JOIN departments dep
        ON d.department_id = dep.department_id

    JOIN schedules s
        ON a.schedule_id = s.schedule_id

    WHERE a.appointment_id = ?
    AND a.user_id = ?
");

$stmt->bind_param(
    "ii",
    $appointment_id,
    $_SESSION["user_id"]
);

$stmt->execute();

$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    die("Appointment not found.");
}
?>
<?php
$pageTitle = "Appointment Confirmation";
require_once "../includes/header.php";
?>

    <div class="card">

        <h1>Appointment Successfully Booked!</h1>

        <p class="success">
            Your appointment request has been submitted successfully.
        </p>

    </div>

    <div class="appointment-card">

        <h2>Appointment Details</h2>

        <p>
            <strong>Appointment ID:</strong>
            #<?= $appointment["appointment_id"] ?>
        </p>

        <p>
            <strong>Patient:</strong>
            <?= htmlspecialchars($_SESSION["full_name"]) ?>
        </p>

        <p>
            <strong>Department:</strong>
            <?= htmlspecialchars($appointment["department_name"]) ?>
        </p>

        <p>
            <strong>Doctor:</strong>
            <?= htmlspecialchars($appointment["doctor_name"]) ?>
        </p>

        <p>
            <strong>Specialization:</strong>
            <?= htmlspecialchars($appointment["specialization"]) ?>
        </p>

        <p>
            <strong>Date:</strong>
            <?= date(
                "F d, Y",
                strtotime($appointment["appointment_date"])
            ) ?>
        </p>

        <p>
            <strong>Time:</strong>
            <?= date(
                "h:i A",
                strtotime($appointment["start_time"])
            ) ?>

            -

            <?= date(
                "h:i A",
                strtotime($appointment["end_time"])
            ) ?>
        </p>

        <p>
            <strong>Queue Number:</strong>
        </p>

        <div class="slot-box">

            <h1>
                <?= $appointment["slot_number"] ?>
            </h1>

            <p>
                Your queue number
            </p>

        </div>

        <p>
            <strong>Status:</strong>

            <span class="status status-<?= strtolower($appointment["status"]) ?>">
                <?= htmlspecialchars($appointment["status"]) ?>
            </span>
        </p>

        <p>
            <strong>Booked On:</strong>
            <?= date(
                "F d, Y h:i A",
                strtotime($appointment["created_at"])
            ) ?>
        </p>

    </div>

    <div class="card">

        <h2>Next Steps</h2>

        <p>
            Your appointment is currently
            <strong>Pending</strong>.
        </p>

        <p>
            Please wait for the administrator to confirm your appointment.
        </p>

        <p>
            You can check your appointment status and notifications anytime.
        </p>

        <div>

            <a
                href="appointments.php"
                class="btn"
            >
                My Appointments
            </a>

            <a
                href="notifications.php"
                class="btn btn-secondary"
            >
                Notifications
            </a>

            <a
                href="dashboard.php"
                class="btn btn-secondary"
            >
                Dashboard
            </a>

        </div>

    </div>

<?php require_once "../includes/footer.php"; ?>