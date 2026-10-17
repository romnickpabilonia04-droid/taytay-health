<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requireLogin();

if ($_SESSION["role"] !== "doctor") {
    header("Location: ../index.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$message = "";

// Kunin ang naka-link na doctor account.
$stmt = $conn->prepare(
    "SELECT doctor_id, doctor_name
     FROM doctors
     WHERE user_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doctor) {
    die("Walang doctor record na naka-link sa account na ito.");
}

$doctor_id = (int) $doctor["doctor_id"];

// ADD, EDIT, at DELETE (soft delete).
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "save";
    $schedule_id = (int) ($_POST["schedule_id"] ?? 0);

    // DELETE: itago lang, huwag permanenteng burahin.
    if ($action === "delete") {
        $stmt = $conn->prepare(
            "UPDATE schedules
             SET is_active = 0
             WHERE schedule_id = ?
               AND doctor_id = ?
               AND is_active = 1"
        );
        $stmt->bind_param("ii", $schedule_id, $doctor_id);
        $stmt->execute();

        $message = $stmt->affected_rows > 0
            ? "Schedule removed from active schedules. Nananatili ang records."
            : "Hindi makita ang schedule o na-remove na ito.";

        $stmt->close();
    } else {
        $appointment_date = $_POST["appointment_date"] ?? "";
        $start_time = $_POST["start_time"] ?? "";
        $end_time = $_POST["end_time"] ?? "";
        $max_slots = (int) ($_POST["max_slots"] ?? 0);

        $date_obj = DateTime::createFromFormat("!Y-m-d", $appointment_date);
        $valid_date = $date_obj &&
            $date_obj->format("Y-m-d") === $appointment_date;

        $start_obj = DateTime::createFromFormat("!H:i", $start_time);
        $valid_start = $start_obj &&
            $start_obj->format("H:i") === $start_time;

        $end_obj = DateTime::createFromFormat("!H:i", $end_time);
        $valid_end = $end_obj &&
            $end_obj->format("H:i") === $end_time;

        if (
            !$valid_date || !$valid_start || !$valid_end ||
            $start_time >= $end_time ||
            $max_slots < 1 || $max_slots > 100
        ) {
            $message = "Invalid ang date, time, o slots.";
        } elseif (
            $schedule_id === 0 &&
            $appointment_date < date("Y-m-d")
        ) {
            $message = "Hindi puwedeng magdagdag ng schedule sa nakaraang date.";
        } else {
            if ($schedule_id === 0) {
                // ADD schedule.
                $stmt = $conn->prepare(
                    "INSERT INTO schedules
                     (doctor_id, appointment_date, start_time, end_time,
                      max_slots, is_active)
                     VALUES (?, ?, ?, ?, ?, 1)"
                );
                $stmt->bind_param(
                    "isssi",
                    $doctor_id,
                    $appointment_date,
                    $start_time,
                    $end_time,
                    $max_slots
                );

                if ($stmt->execute()) {
                    $message = "Matagumpay na naidagdag ang schedule.";
                } else {
                    $message = "Hindi naidagdag ang schedule.";
                }
                $stmt->close();
            } else {
                // Siguraduhing sariling active schedule ang mae-edit.
                $stmt = $conn->prepare(
                    "SELECT schedule_id
                     FROM schedules
                     WHERE schedule_id = ?
                       AND doctor_id = ?
                       AND is_active = 1"
                );
                $stmt->bind_param("ii", $schedule_id, $doctor_id);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$existing) {
                    $message = "Hindi makita ang active schedule o hindi ito sa iyo.";
                } else {
                    // Huwag magtakda ng slots na mas mababa sa existing bookings.
                    $stmt = $conn->prepare(
                        "SELECT COUNT(*) AS total
                         FROM appointments
                         WHERE schedule_id = ?
                           AND status <> 'Cancelled'"
                    );
                    $stmt->bind_param("i", $schedule_id);
                    $stmt->execute();
                    $booking_count = (int) $stmt->get_result()
                        ->fetch_assoc()["total"];
                    $stmt->close();

                    if ($max_slots < $booking_count) {
                        $message = "Hindi puwedeng mas kaunti ang slots kaysa sa existing bookings ($booking_count).";
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE schedules
                             SET appointment_date = ?,
                                 start_time = ?,
                                 end_time = ?,
                                 max_slots = ?
                             WHERE schedule_id = ?
                               AND doctor_id = ?
                               AND is_active = 1"
                        );
                        $stmt->bind_param(
                            "sssiii",
                            $appointment_date,
                            $start_time,
                            $end_time,
                            $max_slots,
                            $schedule_id,
                            $doctor_id
                        );

                        if ($stmt->execute()) {
                            $message = "Matagumpay na na-update ang schedule.";
                        } else {
                            $message = "Hindi na-update ang schedule.";
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// Ipakita lamang ang active schedules ng naka-login na doctor.
$stmt = $conn->prepare(
    "SELECT s.schedule_id, s.appointment_date, s.start_time,
            s.end_time, s.max_slots,
            (
                SELECT COUNT(*)
                FROM appointments a
                WHERE a.schedule_id = s.schedule_id
                  AND a.status <> 'Cancelled'
            ) AS booked_slots
     FROM schedules s
     WHERE s.doctor_id = ?
       AND s.is_active = 1
     ORDER BY s.appointment_date ASC, s.start_time ASC"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$schedules = $stmt->get_result();
?>
<?php
$pageTitle = "My Schedules";
require_once "../includes/header.php";
?>
    <h1>My Schedules</h1>
    <p>Doctor: <?= htmlspecialchars($doctor["doctor_name"]) ?></p>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>Add New Schedule</h2>

    <form method="POST" action="schedules.php">
        <input type="hidden" name="schedule_id" value="0">

        <label for="appointment_date">Schedule Date</label>
        <input type="date" id="appointment_date"
               name="appointment_date"
               min="<?= date("Y-m-d") ?>" required>

        <label for="start_time">Start Time</label>
        <input type="time" id="start_time" name="start_time" required>

        <label for="end_time">End Time</label>
        <input type="time" id="end_time" name="end_time" required>

        <label for="max_slots">Maximum Slots (1–100)</label>
        <input type="number" id="max_slots" name="max_slots"
               min="1" max="100" value="10" required>

        <button type="submit">Add Schedule</button>
    </form>

    <hr>

    <h2>Existing Schedules</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Booked</th>
                    <th>Max Slots</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($schedules->num_rows > 0): ?>
                <?php while ($s = $schedules->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($s["appointment_date"]) ?></td>
                        <td><?= date("h:i A", strtotime($s["start_time"])) ?></td>
                        <td><?= date("h:i A", strtotime($s["end_time"])) ?></td>
                        <td><?= (int) $s["booked_slots"] ?></td>
                        <td><?= (int) $s["max_slots"] ?></td>
                        <td>
                            <details>
                                <summary>Edit</summary>

                                <form method="POST" action="schedules.php">
                                    <input type="hidden" name="schedule_id"
                                           value="<?= (int) $s["schedule_id"] ?>">

                                    <label>Date</label>
                                    <input type="date" name="appointment_date"
                                           value="<?= htmlspecialchars($s["appointment_date"]) ?>"
                                           required>

                                    <label>Start Time</label>
                                    <input type="time" name="start_time"
                                           value="<?= htmlspecialchars(substr($s["start_time"], 0, 5)) ?>"
                                           required>

                                    <label>End Time</label>
                                    <input type="time" name="end_time"
                                           value="<?= htmlspecialchars(substr($s["end_time"], 0, 5)) ?>"
                                           required>

                                    <label>Maximum Slots</label>
                                    <input type="number" name="max_slots"
                                           min="1" max="100"
                                           value="<?= (int) $s["max_slots"] ?>"
                                           required>

                                    <button type="submit" name="action" value="save">
                                        Save Changes
                                    </button>
                                </form>
                            </details>

                            <form method="POST" action="schedules.php"
                                  onsubmit="return confirm('Remove this schedule from active schedules? Existing appointment records will remain.');">
                                <input type="hidden" name="schedule_id"
                                       value="<?= (int) $s["schedule_id"] ?>">
                                <button type="submit" name="action" value="delete">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Wala kang active schedules.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php require_once "../includes/footer.php"; ?>