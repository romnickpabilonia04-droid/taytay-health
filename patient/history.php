<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requirePatient();

$stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.slot_number,
           a.status, d.doctor_name, dep.department_name,
           s.start_time, s.end_time
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN departments dep ON d.department_id = dep.department_id
    JOIN schedules s ON a.schedule_id = s.schedule_id
    WHERE a.user_id = ?
      AND a.status IN ('Completed', 'Cancelled', 'No Show')
    ORDER BY a.appointment_date DESC, s.start_time DESC
");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$history = $stmt->get_result();
?>
<?php
$pageTitle = "Appointment History";
require_once "../includes/header.php";
?>
    <h1>Appointment History</h1>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Department</th><th>Doctor</th>
                    <th>Date</th><th>Time</th><th>Queue #</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($history->num_rows > 0): ?>
                <?php while ($a = $history->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= (int)$a["appointment_id"] ?></td>
                        <td><?= htmlspecialchars($a["department_name"]) ?></td>
                        <td><?= htmlspecialchars($a["doctor_name"]) ?></td>
                        <td><?= date("M d, Y", strtotime($a["appointment_date"])) ?></td>
                        <td>
                            <?= date("h:i A", strtotime($a["start_time"])) ?>
                            -
                            <?= date("h:i A", strtotime($a["end_time"])) ?>
                        </td>
                        <td><?= (int)$a["slot_number"] ?></td>
                        <td><?= htmlspecialchars($a["status"]) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No appointment history yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php require_once "../includes/footer.php"; ?>