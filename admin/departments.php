<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireAdmin();

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["department_name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($name === "") {
        $error = "Department name is required.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO departments (department_name, description)
             VALUES (?, ?)"
        );
        $stmt->bind_param("ss", $name, $description);

        if ($stmt->execute()) {
            $message = "Department added successfully.";
        } else {
            $error = "Department already exists or could not be added.";
        }
    }
}

if (isset($_GET["delete"]) && ctype_digit($_GET["delete"])) {
    $id = (int) $_GET["delete"];

    $check = $conn->prepare(
        "SELECT COUNT(*) AS total FROM doctors WHERE department_id = ?"
    );
    $check->bind_param("i", $id);
    $check->execute();
    $total = (int)$check->get_result()->fetch_assoc()["total"];

    if ($total > 0) {
        $error = "Delete or reassign the department's doctors first.";
    } else {
        $stmt = $conn->prepare(
            "DELETE FROM departments WHERE department_id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = $stmt->affected_rows
            ? "Department deleted."
            : "Department not found.";
    }
}

$departments = $conn->query(
    "SELECT department_id, department_name, description
     FROM departments ORDER BY department_name"
);
?>
<?php
$pageTitle = "Manage Departments";
require_once "../includes/header.php";
?>
    <h1>Manage Departments</h1>

    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <div class="card">
        <h2>Add Department</h2>
        <form method="POST">
            <label>Department Name</label>
            <input type="text" name="department_name" required>

            <label>Description</label>
            <textarea name="description" rows="3"></textarea>

            <button type="submit" class="btn">Add Department</button>
        </form>
    </div>

    <h2>Existing Departments</h2>
    <div class="table-container">
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Action</th></tr></thead>
            <tbody>
            <?php while ($d = $departments->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$d["department_id"] ?></td>
                    <td><?= htmlspecialchars($d["department_name"]) ?></td>
                    <td><?= htmlspecialchars($d["description"] ?? "") ?></td>
                    <td>
                        <a class="btn btn-secondary"
                           href="?delete=<?= (int)$d["department_id"] ?>"
                           onclick="return confirmAction('Delete this department?')">
                           Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php require_once "../includes/footer.php"; ?>