<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requirePatient();

if (!isset($_GET["department_id"]) || !is_numeric($_GET["department_id"])) {
    header("Location: departments.php");
    exit;
}

$department_id = (int) $_GET["department_id"];

$stmt = $conn->prepare("
    SELECT department_name, description
    FROM departments
    WHERE department_id = ?
");

$stmt->bind_param("i", $department_id);
$stmt->execute();

$department = $stmt->get_result()->fetch_assoc();

if (!$department) {
    header("Location: departments.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT doctor_id, doctor_name, specialization
    FROM doctors
    WHERE department_id = ?
    ORDER BY doctor_name ASC
");

$stmt->bind_param("i", $department_id);
$stmt->execute();

$doctors = $stmt->get_result();
?>
<?php
$pageTitle = "Doctors - " . $department["department_name"];
require_once "../includes/header.php";
?>

    <div class="dashboard-header">

        <div>

            <h1>
                <?= htmlspecialchars($department["department_name"]) ?>
            </h1>

            <p class="welcome">
                <?= htmlspecialchars($department["description"]) ?>
            </p>

        </div>

        <a href="departments.php" class="btn">
            ← Back
        </a>

    </div>


    <h2>Available Doctors</h2>


    <?php if ($doctors->num_rows > 0): ?>

        <div class="grid">

            <?php while ($doctor = $doctors->fetch_assoc()): ?>

                <div class="doctor-card">

                    <div class="doctor-avatar">
                        DR
                    </div>

                    <h3>
                        <?= htmlspecialchars($doctor["doctor_name"]) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $doctor["specialization"] ?: "Medical Specialist"
                        ) ?>
                    </p>

                    <a
                        href="schedule.php?doctor_id=<?= $doctor["doctor_id"] ?>"
                        class="btn"
                    >
                        View Schedule →
                    </a>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="card">

            <h3>No doctors available</h3>

            <p class="welcome">
                There are currently no doctors assigned to this department.
            </p>

        </div>

    <?php endif; ?>

<?php require_once "../includes/footer.php"; ?>