<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireAdmin();

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appointment_id = (int) ($_POST["appointment_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    $allowed = ["Pending", "Confirmed", "Completed", "Cancelled", "No Show"];

    if ($appointment_id <= 0 || !in_array($status, $allowed, true)) {
        $error = "Invalid appointment information.";
    } else {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare(
                "SELECT user_id, status FROM appointments
                 WHERE appointment_id = ? FOR UPDATE"
            );
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();

            if (!$appointment) {
                throw new Exception("Appointment not found.");
            }

            $oldStatus = $appointment["status"];

            $update = $conn->prepare(
                "UPDATE appointments SET status = ?
                 WHERE appointment_id = ?"
            );
            $update->bind_param("si", $status, $appointment_id);
            $update->execute();

            if ($oldStatus !== $status) {
                $text = "Your appointment #" . $appointment_id .
                    " status has been updated to " . $status . ".";

                $notify = $conn->prepare(
                    "INSERT INTO notifications
                    (user_id, appointment_id, message)
                    VALUES (?, ?, ?)"
                );
                $notify->bind_param(
                    "iis",
                    $appointment["user_id"],
                    $appointment_id,
                    $text
                );
                $notify->execute();
            }

            $conn->commit();
            $message = "Appointment status updated successfully.";
        } catch (Throwable $e) {
            $conn->rollback();
            $error = "Unable to update appointment.";
        }
    }
}

$sql = "
    SELECT a.appointment_id, a.appointment_date,
           a.slot_number, a.status, a.created_at,
           u.full_name, u.email,
           d.doctor_name, dep.department_name,
           s.start_time, s.end_time
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN departments dep ON d.department_id = dep.department_id
    JOIN schedules s ON a.schedule_id = s.schedule_id
    ORDER BY a.created_at DESC
";
$appointments = $conn->query($sql);
?>
<?php
$pageTitle = "Manage Appointments";
require_once "../includes/header.php";
?>
    <h1>Manage Appointments</h1>

    <?php if ($message): ?>
        <p class="success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Department / Doctor</th>
                    <th>Date / Time</th>
                    <th>Queue #</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($a = $appointments->fetch_assoc()): ?>
                <tr>
                    <td>#<?= (int)$a["appointment_id"] ?></td>
                    <td>
                        <?= htmlspecialchars($a["full_name"]) ?><br>
                        <small><?= htmlspecialchars($a["email"]) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($a["department_name"]) ?><br>
                        <?= htmlspecialchars($a["doctor_name"]) ?>
                    </td>
                    <td>
                        <?= date("M d, Y", strtotime($a["appointment_date"])) ?><br>
                        <?= date("h:i A", strtotime($a["start_time"])) ?>
                        -
                        <?= date("h:i A", strtotime($a["end_time"])) ?>
                    </td>
                    <td><?= (int)$a["slot_number"] ?></td>
                    <td>
                        <span class="status status-<?= strtolower(str_replace(" ", "-", $a["status"])) ?>">
                            <?= htmlspecialchars($a["status"]) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" class="inline-form"
                              onsubmit="return confirmAction('Update this appointment status?')">
                            <input type="hidden" name="appointment_id"
                                   value="<?= (int)$a["appointment_id"] ?>">
                            <select name="status" required>
                                <?php foreach (["Pending", "Confirmed", "Completed", "Cancelled", "No Show"] as $option): ?>
                                    <option value="<?= $option ?>"
                                        <?= $a["status"] === $option ? "selected" : "" ?>>
                                        <?= $option ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php require_once "../includes/footer.php"; ?>