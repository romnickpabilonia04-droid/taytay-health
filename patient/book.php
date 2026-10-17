<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requirePatient();

$schedule_id = isset($_GET["schedule_id"]) ? (int) $_GET["schedule_id"] : 0;

if ($schedule_id <= 0) {
    header("Location: departments.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Schedule Information
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        s.schedule_id,
        s.doctor_id,
        s.appointment_date,
        s.start_time,
        s.end_time,
        s.max_slots,
        d.doctor_name,
        d.specialization,
        dep.department_id,
        dep.department_name
    FROM schedules s
    JOIN doctors d
        ON s.doctor_id = d.doctor_id
    JOIN departments dep
        ON d.department_id = dep.department_id
    WHERE s.schedule_id = ?
");

$stmt->bind_param("i", $schedule_id);
$stmt->execute();

$schedule = $stmt->get_result()->fetch_assoc();

if (!$schedule) {
    die("Schedule not found.");
}

$is_past_schedule = $schedule["appointment_date"] < date("Y-m-d");

/*
|--------------------------------------------------------------------------
    | Count Reserved Slots
|--------------------------------------------------------------------------
*/
$countStmt = $conn->prepare("
    SELECT COUNT(*) AS used_slots
    FROM appointments
    WHERE schedule_id = ?
");

$countStmt->bind_param("i", $schedule_id);
$countStmt->execute();

$used_slots = (int) $countStmt->get_result()->fetch_assoc()["used_slots"];

$max_slots = (int) $schedule["max_slots"];
$available_slots = max(0, $max_slots - $used_slots);

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| Booking
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | Transaction + Schedule Lock
    |--------------------------------------------------------------------------
    */
    $conn->begin_transaction();

    try {

        // Lock the schedule row so two patients cannot take the same last slot.
        $lock = $conn->prepare("
            SELECT
                schedule_id,
                appointment_date,
                max_slots
            FROM schedules
            WHERE schedule_id = ?
            FOR UPDATE
        ");

        $lock->bind_param("i", $schedule_id);
        $lock->execute();

        $lockedSchedule = $lock->get_result()->fetch_assoc();

        if (!$lockedSchedule) {
            throw new Exception("Schedule not found.");
        }

        if ($lockedSchedule["appointment_date"] < date("Y-m-d")) {
            throw new Exception("This schedule has already passed.");
        }

        $lockedMaxSlots = (int) $lockedSchedule["max_slots"];

        /*
        |--------------------------------------------------------------------------
        | Check if patient already booked this schedule
        |--------------------------------------------------------------------------
        */
        $duplicate = $conn->prepare("
            SELECT appointment_id
            FROM appointments
            WHERE user_id = ?
            AND schedule_id = ?
            LIMIT 1
        ");

        $duplicate->bind_param(
            "ii",
            $_SESSION["user_id"],
            $schedule_id
        );

        $duplicate->execute();

        if ($duplicate->get_result()->num_rows > 0) {
            throw new Exception(
                "An appointment record already exists for this schedule. " .
                "Your current database does not allow booking this schedule again, " .
                "including after cancellation."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Count Active Appointments Again
        |--------------------------------------------------------------------------
        */
        $count = $conn->prepare("
            SELECT COUNT(*) AS used_slots
            FROM appointments
            WHERE schedule_id = ?
        ");

        $count->bind_param("i", $schedule_id);
        $count->execute();

        $currentUsed = (int) $count->get_result()->fetch_assoc()["used_slots"];

        /*
        |--------------------------------------------------------------------------
        | 100-Slot Limit
        |--------------------------------------------------------------------------
        */
        if ($currentUsed >= $lockedMaxSlots) {
            throw new Exception("This schedule is fully booked.");
        }

        /*
        |--------------------------------------------------------------------------
        | Find First Available Slot Number
        |--------------------------------------------------------------------------
        */
        $slot = 1;

        $slotQuery = $conn->prepare("
            SELECT slot_number
            FROM appointments
            WHERE schedule_id = ?
            ORDER BY slot_number ASC
        ");

        $slotQuery->bind_param("i", $schedule_id);
        $slotQuery->execute();

        $slotResult = $slotQuery->get_result();

        while ($row = $slotResult->fetch_assoc()) {

            $existingSlot = (int) $row["slot_number"];

            if ($existingSlot == $slot) {
                $slot++;
            } elseif ($existingSlot > $slot) {
                break;
            }
        }

        if ($slot > $lockedMaxSlots) {
            throw new Exception("No unused queue numbers remain for this schedule.");
        }

        /*
        |--------------------------------------------------------------------------
        | Insert Appointment
        |--------------------------------------------------------------------------
        */
        $appointment_date = $lockedSchedule["appointment_date"];
        $user_id = $_SESSION["user_id"];
        $doctor_id = $schedule["doctor_id"];

        $insert = $conn->prepare("
            INSERT INTO appointments
            (
                user_id,
                doctor_id,
                schedule_id,
                appointment_date,
                slot_number,
                status
            )
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");

        $insert->bind_param(
            "iiisi",
            $user_id,
            $doctor_id,
            $schedule_id,
            $appointment_date,
            $slot
        );

        $insert->execute();

        $appointment_id = $conn->insert_id;

        /*
        |--------------------------------------------------------------------------
        | Create Notification
        |--------------------------------------------------------------------------
        */
        $notificationMessage =
            "Your appointment request with " .
            $schedule["doctor_name"] .
            " on " .
            date("F d, Y", strtotime($appointment_date)) .
            " at " .
            date("h:i A", strtotime($schedule["start_time"])) .
            " has been submitted. Queue number: " .
            $slot .
            ". Status: Pending.";

        $notification = $conn->prepare("
            INSERT INTO notifications
            (
                user_id,
                appointment_id,
                message
            )
            VALUES (?, ?, ?)
        ");

        $notification->bind_param(
            "iis",
            $user_id,
            $appointment_id,
            $notificationMessage
        );

        $notification->execute();

        /*
        |--------------------------------------------------------------------------
        | Commit
        |--------------------------------------------------------------------------
        */
        $conn->commit();

        header(
            "Location: confirmation.php?appointment_id=" .
            $appointment_id
        );
        exit;

    } catch (Exception $e) {

        $conn->rollback();

        $error = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| Refresh Slot Information
|--------------------------------------------------------------------------
*/
$countStmt = $conn->prepare("
    SELECT COUNT(*) AS used_slots
    FROM appointments
    WHERE schedule_id = ?
");

$countStmt->bind_param("i", $schedule_id);
$countStmt->execute();

$used_slots = (int) $countStmt->get_result()->fetch_assoc()["used_slots"];

$available_slots = max(0, $max_slots - $used_slots);

?>
<?php
$pageTitle = "Book Appointment";
require_once "../includes/header.php";
?>

    <h1>Book Appointment</h1>

    <?php if ($error): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="appointment-card">

        <h2>
            <?= htmlspecialchars($schedule["doctor_name"]) ?>
        </h2>

        <p>
            <strong>Department:</strong>
            <?= htmlspecialchars($schedule["department_name"]) ?>
        </p>

        <p>
            <strong>Specialization:</strong>
            <?= htmlspecialchars($schedule["specialization"]) ?>
        </p>

        <p>
            <strong>Date:</strong>
            <?= date(
                "F d, Y",
                strtotime($schedule["appointment_date"])
            ) ?>
        </p>

        <p>
            <strong>Time:</strong>
            <?= date(
                "h:i A",
                strtotime($schedule["start_time"])
            ) ?>
            -
            <?= date(
                "h:i A",
                strtotime($schedule["end_time"])
            ) ?>
        </p>

    </div>

    <div class="slot-box">

        <h2>Appointment Slots</h2>

        <p>
            <strong>Total Slots:</strong>
            <?= $max_slots ?>
        </p>

        <p>
            <strong>Used Slots:</strong>
            <?= $used_slots ?>
        </p>

        <p>
            <strong>Available Slots:</strong>
            <?= $available_slots ?>
        </p>

    </div>

    <?php if ($is_past_schedule): ?>

        <div class="alert error">
            This schedule has already passed.
        </div>

    <?php elseif ($available_slots > 0): ?>

        <div class="card">

            <h2>Confirm Booking</h2>

            <p>
                You are about to book an appointment with
                <strong>
                    <?= htmlspecialchars($schedule["doctor_name"]) ?>
                </strong>.
            </p>

            <p>
                Your queue number will be assigned automatically.
            </p>

            <form method="POST">

                <button
                    type="submit"
                    class="btn"
                    onclick="return confirm('Are you sure you want to book this appointment?');"
                >
                    Confirm Appointment
                </button>

                <a
                    href="schedule.php?doctor_id=<?= $schedule["doctor_id"] ?>"
                    class="btn btn-secondary"
                >
                    Back
                </a>

            </form>

        </div>

    <?php else: ?>

        <div class="alert error">

            This schedule is fully booked.

            <br><br>

            <a
                href="schedule.php?doctor_id=<?= $schedule["doctor_id"] ?>"
                class="btn"
            >
                Back to Schedule
            </a>

        </div>

    <?php endif; ?>

<?php require_once "../includes/footer.php"; ?>