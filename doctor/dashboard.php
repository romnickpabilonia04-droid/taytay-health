<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requireLogin();

if ($_SESSION["role"] !== "doctor") {
    header("Location: ../index.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

// Hanapin ang doctor record na naka-link sa naka-login na account.
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
$message = "";

// I-process ang Confirm, Complete, at Cancel actions.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appointment_id = (int) ($_POST["appointment_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($action === "confirm") {
        $new_status = "Confirmed";
    } elseif ($action === "complete") {
        $new_status = "Completed";
    } elseif ($action === "cancel") {
        $new_status = "Cancelled";
    } else {
        $new_status = "";
    }

    if ($appointment_id > 0 && $new_status !== "") {
        $conn->begin_transaction();

        try {
            // I-lock ang appointment at tiyaking sa doctor na ito ito.
            $stmt = $conn->prepare(
                "SELECT user_id, status
                 FROM appointments
                 WHERE appointment_id = ?
                   AND doctor_id = ?
                 FOR UPDATE"
            );
            $stmt->bind_param("ii", $appointment_id, $doctor_id);
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $allowed = false;

            if ($appointment) {
                if (
                    $action === "confirm" &&
                    $appointment["status"] === "Pending"
                ) {
                    $allowed = true;
                } elseif (
                    $action === "complete" &&
                    $appointment["status"] === "Confirmed"
                ) {
                    $allowed = true;
                } elseif (
                    $action === "cancel" &&
                    in_array(
                        $appointment["status"],
                        ["Pending", "Confirmed"],
                        true
                    )
                ) {
                    $allowed = true;
                }
            }

            if ($allowed) {
                $stmt = $conn->prepare(
                    "UPDATE appointments
                     SET status = ?
                     WHERE appointment_id = ?
                       AND doctor_id = ?"
                );
                $stmt->bind_param(
                    "sii",
                    $new_status,
                    $appointment_id,
                    $doctor_id
                );
                $stmt->execute();
                $stmt->close();

                // Gumawa ng notification para sa patient.
                $patient_id = (int) $appointment["user_id"];

                $notification = "Your appointment #"
                    . $appointment_id
                    . " with "
                    . $doctor["doctor_name"]
                    . " has been "
                    . strtolower($new_status)
                    . ".";

                $stmt = $conn->prepare(
                    "INSERT INTO notifications
                     (user_id, appointment_id, message, is_read, created_at)
                     VALUES (?, ?, ?, 0, NOW())"
                );
                $stmt->bind_param(
                    "iis",
                    $patient_id,
                    $appointment_id,
                    $notification
                );
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Appointment #"
                    . $appointment_id
                    . " successfully "
                    . strtolower($new_status)
                    . ".";
            } else {
                $conn->rollback();
                $message = "Hindi ma-update ang appointment. Maaaring "
                    . "hindi ito naka-assign sa iyo o nabago na ang status.";
            }
        } catch (Throwable $e) {
            $conn->rollback();
            $message = "May error sa pag-update ng appointment.";
        }
    } else {
        $message = "Invalid na appointment action.";
    }
}

// Kunin ang lahat ng appointments na naka-assign sa doctor na ito.
$stmt = $conn->prepare(
    "SELECT a.appointment_id, a.appointment_date, a.slot_number,
            a.status, u.full_name, d.doctor_name,
            dep.department_name, s.start_time, s.end_time
     FROM appointments a
     JOIN users u ON a.user_id = u.user_id
     JOIN doctors d ON a.doctor_id = d.doctor_id
     JOIN departments dep ON d.department_id = dep.department_id
     JOIN schedules s ON a.schedule_id = s.schedule_id
     WHERE a.doctor_id = ?
     ORDER BY a.appointment_date DESC, s.start_time DESC"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>
<?php
$pageTitle = "Doctor Dashboard";
require_once "../includes/header.php";
?>
    <h1>Doctor Dashboard</h1>
    <p>
        Welcome, <?= htmlspecialchars($doctor["doctor_name"]) ?>.
        Here are your appointments.
    </p>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Appointment</th>
                    <th>Patient</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Queue #</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($appointments->num_rows > 0): ?>
                <?php while ($a = $appointments->fetch_assoc()): ?>
                    <tr>
                        <td>
                            #<?= (int) $a["appointment_id"] ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($a["full_name"]) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($a["department_name"]) ?>
                        </td>
                        <td>
                            <?= date(
                                "M d, Y",
                                strtotime($a["appointment_date"])
                            ) ?>
                        </td>
                        <td>
                            <?= date(
                                "h:i A",
                                strtotime($a["start_time"])
                            ) ?>
                            -
                            <?= date(
                                "h:i A",
                                strtotime($a["end_time"])
                            ) ?>
                        </td>
                        <td>
                            <?= (int) $a["slot_number"] ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($a["status"]) ?>
                        </td>
                        <td>
                            <?php if ($a["status"] === "Pending"): ?>
                                <form method="POST" action="dashboard.php">
                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int) $a["appointment_id"] ?>"
                                    >
                                    <button
                                        type="submit"
                                        name="action"
                                        value="confirm"
                                    >
                                        Confirm
                                    </button>
                                    <button
                                        type="submit"
                                        name="action"
                                        value="cancel"
                                        onclick="return confirm('Cancel this appointment?');"
                                    >
                                        Cancel
                                    </button>
                                </form>
                            <?php elseif ($a["status"] === "Confirmed"): ?>
                                <form method="POST" action="dashboard.php">
                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int) $a["appointment_id"] ?>"
                                    >
                                    <button
                                        type="submit"
                                        name="action"
                                        value="complete"
                                        onclick="return confirm('Mark this appointment as completed?');"
                                    >
                                        Mark Complete
                                    </button>
                                    <button
                                        type="submit"
                                        name="action"
                                        value="cancel"
                                        onclick="return confirm('Cancel this appointment?');"
                                    >
                                        Cancel
                                    </button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No appointments assigned to you yet.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php require_once "../includes/footer.php"; ?>