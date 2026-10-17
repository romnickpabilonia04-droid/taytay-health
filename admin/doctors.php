<?php
require_once "../config/database.php";
require_once "../includes/auth.php";

requireAdmin();

$pageTitle = "Manage Doctors";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $department_id = (int)($_POST["department_id"] ?? 0);
    $doctor_name = trim($_POST["doctor_name"] ?? "");
    $specialization = trim($_POST["specialization"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $contact = trim($_POST["contact_number"] ?? "");

    if ($department_id <= 0 || $doctor_name === "") {
        $error = "Doctor name and department are required.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO doctors
            (department_id, doctor_name, specialization, email, contact_number)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issss",
            $department_id,
            $doctor_name,
            $specialization,
            $email,
            $contact
        );

        if ($stmt->execute()) {
            $message = "Doctor added successfully.";
        } else {
            $error = "Could not add doctor. Check for duplicate doctor names.";
        }

        $stmt->close();
    }
}

if (isset($_GET["delete"]) && ctype_digit($_GET["delete"])) {
    $id = (int)$_GET["delete"];

    $check = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM appointments
         WHERE doctor_id = ?"
    );

    $check->bind_param("i", $id);
    $check->execute();

    $total = (int)$check->get_result()->fetch_assoc()["total"];
    $check->close();

    if ($total > 0) {
        $error = "Cannot delete a doctor who has appointment records.";
    } else {
        $stmt = $conn->prepare(
            "DELETE FROM doctors WHERE doctor_id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $message = $stmt->affected_rows
            ? "Doctor deleted."
            : "Doctor not found.";

        $stmt->close();
    }
}

$departments = $conn->query(
    "SELECT department_id, department_name
     FROM departments
     ORDER BY department_name"
);

$doctors = $conn->query(
    "SELECT d.doctor_id, d.doctor_name, d.specialization,
            d.email, d.contact_number, dep.department_name
     FROM doctors d
     JOIN departments dep
        ON d.department_id = dep.department_id
     ORDER BY dep.department_name, d.doctor_name"
);

require_once "../includes/header.php";
?>

<h1>Manage Doctors</h1>
<p class="welcome">Add and manage the doctors in Taytay Health.</p>

<?php if ($message): ?>
    <p class="success"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<div class="card" style="margin-bottom:30px;">
    <h2>Add Doctor</h2>

    <form method="POST">
        <label for="doctor_name">Doctor Name</label>
        <input
            type="text"
            id="doctor_name"
            name="doctor_name"
            required
        >

        <label for="department_id">Department</label>
        <select id="department_id" name="department_id" required>
            <option value="">Select department</option>

            <?php while ($dep = $departments->fetch_assoc()): ?>
                <option value="<?= (int)$dep["department_id"] ?>">
                    <?= htmlspecialchars($dep["department_name"]) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="specialization">Specialization</label>
        <input
            type="text"
            id="specialization"
            name="specialization"
        >

        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
        >

        <label for="contact_number">Contact Number</label>
        <input
            type="text"
            id="contact_number"
            name="contact_number"
        >

        <button type="submit" class="btn">Add Doctor</button>
    </form>
</div>

<h2>Doctor List</h2>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Specialization</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($d = $doctors->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$d["doctor_id"] ?></td>

                    <td>
                        <?= htmlspecialchars($d["doctor_name"]) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($d["department_name"]) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($d["specialization"] ?? "") ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($d["email"] ?? "") ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($d["contact_number"] ?? "") ?>
                    </td>

                    <td>
                        <a
                            class="btn btn-danger"
                            href="?delete=<?= (int)$d["doctor_id"] ?>"
                            onclick="return confirm('Delete this doctor?')"
                        >Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once "../includes/footer.php"; ?>