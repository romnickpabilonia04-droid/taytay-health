<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requirePatient();

$user_id = (int)$_SESSION["user_id"];
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $contact = trim($_POST["contact_number"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $dob = trim($_POST["date_of_birth"] ?? "");
    $gender = trim($_POST["gender"] ?? "");

    if ($full_name === "") {
        $error = "Full name is required.";
    } elseif ($dob !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        $error = "Invalid date of birth.";
    } else {
        $dobValue = $dob !== "" ? $dob : null;
        $stmt = $conn->prepare("
            UPDATE users
            SET full_name = ?, contact_number = ?, address = ?,
                date_of_birth = ?, gender = ?
            WHERE user_id = ? AND role = 'patient'
        ");
        $stmt->bind_param(
            "sssssi",
            $full_name,
            $contact,
            $address,
            $dobValue,
            $gender,
            $user_id
        );

        if ($stmt->execute()) {
            $_SESSION["full_name"] = $full_name;
            $message = "Profile updated successfully.";
        } else {
            $error = "Unable to update profile.";
        }
    }
}

$stmt = $conn->prepare("
    SELECT full_name, username, email, contact_number,
           address, date_of_birth, gender
    FROM users
    WHERE user_id = ? AND role = 'patient'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Patient profile not found.");
}
?>
<?php
$pageTitle = "My Profile";
require_once "../includes/header.php";
?>
    <h1>My Profile</h1>

    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <div class="card">
        <form method="POST">
            <label>Full Name</label>
            <input name="full_name" required
                   value="<?= htmlspecialchars($user["full_name"]) ?>">

            <label>Username</label>
            <input value="<?= htmlspecialchars($user["username"]) ?>" disabled>

            <label>Email</label>
            <input type="email" value="<?= htmlspecialchars($user["email"]) ?>" disabled>

            <label>Contact Number</label>
            <input name="contact_number"
                   value="<?= htmlspecialchars($user["contact_number"] ?? "") ?>">

            <label>Address</label>
            <input name="address"
                   value="<?= htmlspecialchars($user["address"] ?? "") ?>">

            <label>Date of Birth</label>
            <input type="date" name="date_of_birth"
                   value="<?= htmlspecialchars($user["date_of_birth"] ?? "") ?>">

            <label>Gender</label>
            <select name="gender">
                <option value="">Select</option>
                <?php foreach (["Male", "Female", "Prefer not to say"] as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>"
                        <?= ($user["gender"] ?? "") === $option ? "selected" : "" ?>>
                        <?= htmlspecialchars($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
<?php require_once "../includes/footer.php"; ?>