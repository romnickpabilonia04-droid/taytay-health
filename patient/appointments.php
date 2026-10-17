<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requirePatient();

$user_id = $_SESSION["user_id"];

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

    WHERE a.user_id = ?

    ORDER BY
        a.appointment_date DESC,
        s.start_time DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$appointments = $stmt->get_result();
?>
<?php
$pageTitle = "My Appointments";
require_once "../includes/header.php";
?>

    <h1>My Appointments</h1>

    <p>
        View all your medical appointments below.
    </p>

    <?php if ($appointments->num_rows > 0): ?>

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Appointment ID</th>

                        <th>Department</th>

                        <th>Doctor</th>

                        <th>Date</th>

                        <th>Time</th>

                        <th>Queue #</th>

                        <th>Status</th>

                        <th>Details</th>

                    </tr>

                </thead>

                <tbody>

                <?php while ($appointment = $appointments->fetch_assoc()): ?>

                    <tr>

                        <td>
                            #<?= $appointment["appointment_id"] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $appointment["department_name"]
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $appointment["doctor_name"]
                            ) ?>

                            <br>

                            <small>
                                <?= htmlspecialchars(
                                    $appointment["specialization"]
                                ) ?>
                            </small>
                        </td>

                        <td>
                            <?= date(
                                "M d, Y",
                                strtotime(
                                    $appointment["appointment_date"]
                                )
                            ) ?>
                        </td>

                        <td>

                            <?= date(
                                "h:i A",
                                strtotime(
                                    $appointment["start_time"]
                                )
                            ) ?>

                            -

                            <?= date(
                                "h:i A",
                                strtotime(
                                    $appointment["end_time"]
                                )
                            ) ?>

                        </td>

                        <td>

                            <strong>
                                <?= $appointment["slot_number"] ?>
                            </strong>

                        </td>

                        <td>

                            <?php
                            $statusClass = strtolower(
                                str_replace(
                                    " ",
                                    "-",
                                    $appointment["status"]
                                )
                            );
                            ?>

                            <span
                                class="status status-<?= $statusClass ?>"
                            >
                                <?= htmlspecialchars(
                                    $appointment["status"]
                                ) ?>
                            </span>

                        </td>

                        <td>

                            <a
                                href="confirmation.php?appointment_id=<?= $appointment["appointment_id"] ?>"
                                class="btn"
                            >
                                View
                            </a>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="card">

            <h2>No Appointments Yet</h2>

            <p>
                You don't have any appointments yet.
            </p>

            <a
                href="departments.php"
                class="btn"
            >
                Book an Appointment
            </a>

        </div>

    <?php endif; ?>

<?php require_once "../includes/footer.php"; ?>