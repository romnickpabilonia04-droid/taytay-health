<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requirePatient();

$result = $conn->query("
    SELECT department_id, department_name, description
    FROM departments
    ORDER BY department_name ASC
");
?>
<?php
$pageTitle = "Departments";
require_once "../includes/header.php";
?>

    <div class="dashboard-header">

        <div>
            <h1>Medical Departments</h1>

            <p class="welcome">
                Choose a department to find an available doctor.
            </p>
        </div>

    </div>


    <div class="grid">

        <?php while ($department = $result->fetch_assoc()): ?>

            <div class="department-card">

                <div class="department-icon">

                    <?php
                    $icons = [
                        "Pediatrics" => "👶",
                        "Obstetrics and Gynecology (OB-GYN)" => "👩‍⚕️",
                        "Cardiology" => "❤️",
                        "Dermatology" => "🧴",
                        "Internal Medicine" => "🩺"
                    ];

                    echo $icons[$department["department_name"]] ?? "🏥";
                    ?>

                </div>

                <h3>
                    <?= htmlspecialchars($department["department_name"]) ?>
                </h3>

                <p>
                    <?= htmlspecialchars($department["description"]) ?>
                </p>

                <a
                    href="doctors.php?department_id=<?= $department["department_id"] ?>"
                    class="btn"
                >
                    View Doctors →
                </a>

            </div>

        <?php endwhile; ?>

    </div>

<?php require_once "../includes/footer.php"; ?>