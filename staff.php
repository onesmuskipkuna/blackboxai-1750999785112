<?php
require_once 'db_connect.php';
require_once 'functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? '');
    $designation = sanitizeInput($_POST['designation'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');

    if (!$name) {
        $errors[] = 'Staff name is required.';
    }
    if (!$role) {
        $errors[] = 'Role is required.';
    }
    if (!$designation) {
        $errors[] = 'Designation is required.';
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    if ($phone && !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = 'Invalid phone number format.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO staff (name, role, designation, contact_email, contact_phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $role, $designation, $email, $phone]);
            $success = 'Staff member added successfully.';
        } catch (Exception $e) {
            logError("Error adding staff: " . $e->getMessage());
            $errors[] = 'Failed to add staff member. Please try again.';
        }
    }
}

// Fetch staff list
$stmt = $pdo->query("SELECT * FROM staff ORDER BY id DESC");
$staff_list = $stmt->fetchAll();

include 'header.php';
?>

<h1>Staff Management</h1>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <ul>
        <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" class="mb-4">
    <div class="mb-3">
        <label for="name" class="form-label">Staff Name</label>
        <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <input type="text" class="form-control" id="role" name="role" required placeholder="e.g., cleaner, trainer, reception" value="<?= htmlspecialchars($_POST['role'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="designation" class="form-label">Designation</label>
        <input type="text" class="form-control" id="designation" name="designation" required placeholder="e.g., main gym cleaner" value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Contact Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">Contact Phone</label>
        <input type="text" class="form-control" id="phone" name="phone" placeholder="+1234567890" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-primary">Add Staff Member</button>
</form>

<h2>Staff List</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Role</th>
            <th>Designation</th>
            <th>Email</th>
            <th>Phone</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($staff_list): ?>
            <?php foreach ($staff_list as $staff): ?>
            <tr>
                <td><?= htmlspecialchars($staff['id']) ?></td>
                <td><?= htmlspecialchars($staff['name']) ?></td>
                <td><?= htmlspecialchars($staff['role']) ?></td>
                <td><?= htmlspecialchars($staff['designation']) ?></td>
                <td><?= htmlspecialchars($staff['contact_email']) ?></td>
                <td><?= htmlspecialchars($staff['contact_phone']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No staff members found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>
