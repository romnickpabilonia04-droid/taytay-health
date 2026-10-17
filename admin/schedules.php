<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requireAdmin();

$message = "";
$error = "";

/* =========================
   ADD SCHEDULE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_schedule"])) {
    $doctor_id = (int) ($_POST["doctor_id"] ?? 0);
    $appointment_date = $_POST["appointment_date"] ?? "";
    $start_time = $_POST["start_time"] ?? "";
    $end_time = $_POST["end_time"] ?? "";

    if (
        $doctor_id <= 0 ||
        empty($appointment_date) ||
        empty($start_time) ||
        empty($end_time)
    ) {
        $error = "Please fill in all fields.";
    } elseif ($appointment_date < date("Y-m-d")) {
        $error = "Appointment date cannot be in the past.";
    } elseif ($start_time >= $end_time) {
        $error = "End time must be later than start time.";
    } else {
        // Check kung valid ang doctor.
        $doctor_check = $conn->prepare(
            "SELECT doctor_id FROM doctors WHERE doctor_id = ?"
        );
        $doctor_check->bind_param("i", $doctor_id);
        $doctor_check->execute();
        $valid_doctor = $doctor_check->get_result()->num_rows > 0;
        $doctor_check->close();

        if (!$valid_doctor) {
            $error = "Selected doctor does not exist.";
        } else {
            // Check duplicate active schedule.
            $check = $conn->prepare("
                SELECT schedule_id
                FROM schedules
                WHERE doctor_id = ?
                  AND appointment_date = ?
                  AND start_time = ?
                  AND end_time = ?
                  AND is_active = 1
            ");

            $check->bind_param(
                "isss",
                $doctor_id,
                $appointment_date,
                $start_time,
                $end_time
            );
            $check->execute();
            $existing = $check->get_result()->num_rows > 0;
            $check->close();

            if ($existing) {
                $error = "This active schedule already exists.";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO schedules
                    (
                        doctor_id,
                        appointment_date,
                        start_time,
                        end_time,
                        max_slots,
                        is_active
                    )
                    VALUES (?, ?, ?, ?, 100, 1)
                ");

                $stmt->bind_param(
                    "isss",
                    $doctor_id,
                    $appointment_date,
                    $start_time,
                    $end_time
                );

                if ($stmt->execute()) {
                    $message = "Schedule added successfully.";
                } else {
                    $error = "Failed to add schedule.";
                }

                $stmt->close();
            }
        }
    }
}

/* =========================
   DEACTIVATE SCHEDULE
========================= */

if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $schedule_id = (int) $_GET["delete"];

    // Check for active appointments first.
    $check = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM appointments
        WHERE schedule_id = ?
          AND status IN ('Pending', 'Confirmed')
    ");

    $check->bind_param("i", $schedule_id);
    $check->execute();
    $active = (int) $check->get_result()->fetch_assoc()["total"];
    $check->close();

    if ($active > 0) {
        $error = "Cannot remove a schedule with Pending or Confirmed appointments.";
    } else {
        // Soft delete: preserve the schedule and its appointment history.
        $stmt = $conn->prepare("
            UPDATE schedules
            SET is_active = 0
            WHERE schedule_id = ?
              AND is_active = 1
        ");

        $stmt->bind_param("i", $schedule_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Schedule removed successfully. Appointment records were preserved.";
        } else {
            $error = "Schedule not found or already removed.";
        }

        $stmt->close();
    }
}

/* =========================
   GET DOCTORS
========================= */

$doctors = $conn->query("
    SELECT
        d.doctor_id,
        d.doctor_name,
        d.specialization,
        dep.department_name
    FROM doctors d
    JOIN departments dep
        ON d.department_id = dep.department_id
    ORDER BY dep.department_name, d.doctor_name
");

/* =========================
   GET ACTIVE SCHEDULES
========================= */

$schedules = $conn->query("
    SELECT
        s.schedule_id,
        s.appointment_date,
        s.start_time,
        s.end_time,
        s.max_slots,
        d.doctor_name,
        d.specialization,
        dep.department_name,
        (
            SELECT COUNT(*)
            FROM appointments a
            WHERE a.schedule_id = s.schedule_id
              AND a.status IN ('Pending', 'Confirmed')
        ) AS used_slots
    FROM schedules s
    JOIN doctors d
        ON s.doctor_id = d.doctor_id
    JOIN departments dep
        ON d.department_id = dep.department_id
    WHERE s.is_active = 1
    ORDER BY s.appointment_date ASC, s.start_time ASC
");
?>
<?php
$pageTitle = "Manage Schedules";
require_once "../includes/header.php";
?>

    <div class="dashboard-header">
        <div>
            <h1>Manage Schedules</h1>
            <p class="welcome">Create and manage doctor appointment schedules.</p>
        </div>
    </div>

    <?php if ($message !== ""): ?>
        <div class="success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Add New Schedule</h2>

        <form method="POST" action="schedules.php">
            <input type="hidden" name="add_schedule" value="1">

            <label for="doctor_id">Doctor</label>
            <select name="doctor_id" id="doctor_id" required>
                <option value="">Select Doctor</option>

                <?php while ($doctor = $doctors->fetch_assoc()): ?>
                    <option value="<?= (int) $doctor["doctor_id"] ?>">
                        <?= htmlspecialchars($doctor["doctor_name"]) ?>
                        - <?= htmlspecialchars($doctor["department_name"]) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="appointment_date">Appointment Date</label>
            <input
                type="date"
                id="appointment_date"
                name="appointment_date"
                min="<?= date("Y-m-d") ?>"
                required
            >

            <label for="start_time">Start Time</label>
            <input type="time" id="start_time" name="start_time" required>

            <label for="end_time">End Time</label>
            <input type="time" id="end_time" name="end_time" required>

            <div class="slot-box">
                <div class="slot-number">100</div>
                <strong>Maximum Appointment Slots</strong>
                <p>Each schedule can accept up to 100 active appointments.</p>
            </div>

            <button type="submit">+ Add Schedule</button>
        </form>
    </div>

    <div style="margin-top:40px;">
        <h2>Existing Schedules</h2>

        <?php if ($schedules && $schedules->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Slots</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php while ($schedule = $schedules->fetch_assoc()): ?>
                        <?php
                            $used = (int) $schedule["used_slots"];
                            $max = (int) $schedule["max_slots"];
                            $available = max(0, $max - $used);
                        ?>

                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars($schedule["doctor_name"]) ?>
                                </strong>
                                <br>
                                <small>
                                    <?= htmlspecialchars($schedule["specialization"]) ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars($schedule["department_name"]) ?>
                            </td>

                            <td>
                                <?= date("M d, Y", strtotime($schedule["appointment_date"])) ?>
                            </td>

                            <td>
                                <?= date("h:i A", strtotime($schedule["start_time"])) ?>
                                -
                                <?= date("h:i A", strtotime($schedule["end_time"])) ?>
                            </td>

                            <td>
                                <strong><?= $used ?></strong> / <?= $max ?>
                                <br>
                                <small><?= $available ?> available</small>
                            </td>

                            <td>
                                <?php if ($available <= 0): ?>
                                    <span class="status status-cancelled">FULL</span>
                                <?php else: ?>
                                    <span class="status status-confirmed">AVAILABLE</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a
                                    href="schedules.php?delete=<?= (int) $schedule["schedule_id"] ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Remove this schedule from active schedules? Existing appointment history will be preserved.');"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card">
                <h3>No active schedules</h3>
                <p class="welcome">Add a new doctor schedule above.</p>
            </div>
        <?php endif; ?>
    </div>

<?php require_once "../includes/footer.php"; ?>