<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requirePatient();

if (!isset($_GET["doctor_id"]) || !is_numeric($_GET["doctor_id"])) {
    header("Location: departments.php");
    exit;
}

$doctor_id = (int) $_GET["doctor_id"];

$stmt = $conn->prepare("
    SELECT 
        d.doctor_id,
        d.department_id,
        d.doctor_name,
        d.specialization,
        dep.department_name
    FROM doctors d
    JOIN departments dep 
        ON d.department_id = dep.department_id
    WHERE d.doctor_id = ?
");

$stmt->bind_param("i", $doctor_id);
$stmt->execute();

$doctor = $stmt->get_result()->fetch_assoc();

if (!$doctor) {
    header("Location: departments.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get schedules
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        s.schedule_id,
        s.appointment_date,
        s.start_time,
        s.end_time,
        s.max_slots,

        (
            SELECT COUNT(*)
            FROM appointments a
            WHERE a.schedule_id = s.schedule_id
        ) AS used_slots

    FROM schedules s

    WHERE s.doctor_id = ?
    AND s.appointment_date >= CURDATE()

    ORDER BY s.appointment_date ASC,
             s.start_time ASC
");

$stmt->bind_param("i", $doctor_id);
$stmt->execute();

$schedules = $stmt->get_result();

?>
<?php
$pageTitle = "Schedule - " . $doctor["doctor_name"];
require_once "../includes/header.php";
?>


    <!-- DOCTOR INFORMATION -->

    <div class="dashboard-header">

        <div>

            <h1>
                <?= htmlspecialchars($doctor["doctor_name"]) ?>
            </h1>

            <p class="welcome">

                <?= htmlspecialchars(
                    $doctor["specialization"]
                ) ?>

                ·

                <?= htmlspecialchars(
                    $doctor["department_name"]
                ) ?>

            </p>

        </div>


        <a
            href="doctors.php?department_id=<?= (int) $doctor["department_id"] ?>"
            class="btn"
        >
            ← Back
        </a>

    </div>


    <h2>
        Available Schedules
    </h2>


    <?php if ($schedules->num_rows > 0): ?>


        <div class="grid">


            <?php while ($schedule = $schedules->fetch_assoc()): ?>


                <?php

                $max_slots = (int) $schedule["max_slots"];

                $used_slots = (int) $schedule["used_slots"];

                $available_slots = max(0, $max_slots - $used_slots);

                $is_full = $available_slots <= 0;

                ?>


                <div class="appointment-card">


                    <h3>

                        📅

                        <?= date(
                            "F d, Y",
                            strtotime(
                                $schedule["appointment_date"]
                            )
                        ) ?>

                    </h3>


                    <p>

                        🕐

                        <?= date(
                            "h:i A",
                            strtotime(
                                $schedule["start_time"]
                            )
                        ) ?>

                        -

                        <?= date(
                            "h:i A",
                            strtotime(
                                $schedule["end_time"]
                            )
                        ) ?>

                    </p>


                    <div class="slot-box">


                        <div class="slot-number">

                            <?= $available_slots ?>

                        </div>


                        <strong>
                            Available Slots
                        </strong>


                        <p>

                            Used:

                            <?= $used_slots ?>

                            /

                            <?= $max_slots ?>

                        </p>


                    </div>


                    <?php if ($is_full): ?>

                        <div class="error">

                            This schedule is fully booked.

                        </div>


                        <button
                            type="button"
                            disabled
                            style="
                                width:100%;
                                opacity:.5;
                                cursor:not-allowed;
                            "
                        >

                            Fully Booked

                        </button>


                    <?php else: ?>


                        <a
                            href="book.php?schedule_id=<?= $schedule["schedule_id"] ?>"
                            class="btn"
                            style="width:100%; text-align:center;"
                        >

                            Book This Schedule →

                        </a>


                    <?php endif; ?>


                </div>


            <?php endwhile; ?>


        </div>


    <?php else: ?>


        <div class="card">

            <h3>
                No schedules available
            </h3>

            <p class="welcome">

                There are currently no available
                schedules for this doctor.

            </p>

        </div>


    <?php endif; ?>


<?php require_once "../includes/footer.php"; ?>