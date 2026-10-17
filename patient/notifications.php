<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requirePatient();

$user_id = (int)$_SESSION["user_id"];

$markRead = $conn->prepare(
    "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
);
$markRead->bind_param("i", $user_id);
$markRead->execute();

$stmt = $conn->prepare("
    SELECT notification_id, appointment_id, message, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>
<?php
$pageTitle = "My Notifications";
require_once "../includes/header.php";
?>
    <h1>My Notifications</h1>

    <?php if ($notifications->num_rows > 0): ?>
        <?php while ($n = $notifications->fetch_assoc()): ?>
            <div class="card">
                <p><?= nl2br(htmlspecialchars($n["message"])) ?></p>
                <small>
                    <?= date("M d, Y h:i A", strtotime($n["created_at"])) ?>
                </small>

                <?php if ($n["appointment_id"]): ?>
                    <p>
                        <a class="btn"
                           href="confirmation.php?appointment_id=<?= (int)$n["appointment_id"] ?>">
                            View Appointment
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">
            <h2>No Notifications Yet</h2>
            <p>Booking updates and appointment status messages will appear here.</p>
        </div>
    <?php endif; ?>
<?php require_once "../includes/footer.php"; ?>