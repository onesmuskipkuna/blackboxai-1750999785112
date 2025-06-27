<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? '');

    if (!$username) {
        $errors[] = 'Username is required.';
    }
    if (!$password) {
        $errors[] = 'Password is required.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$role) {
        $errors[] = 'Role is required.';
    }

    if (empty($errors)) {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username already exists.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $password_hash, $role]);
                $success = 'User registered successfully.';
            }
        } catch (Exception $e) {
            logError("User registration error: " . $e->getMessage());
            $errors[] = 'Failed to register user. Please try again.';
        }
    }
}

include 'header.php';
?>

<h1>Register New User</h1>

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

<form method="post" class="w-50 mx-auto">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <div class="mb-3">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
    </div>
    <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <select class="form-select" id="role" name="role" required>
            <option value="">Select Role</option>
            <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
            <option value="supervisor" <?= (isset($_POST['role']) && $_POST['role'] == 'supervisor') ? 'selected' : '' ?>>Supervisor</option>
            <option value="maintenance" <?= (isset($_POST['role']) && $_POST['role'] == 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
            <option value="trainer" <?= (isset($_POST['role']) && $_POST['role'] == 'trainer') ? 'selected' : '' ?>>Trainer</option>
            <option value="cleaner" <?= (isset($_POST['role']) && $_POST['role'] == 'cleaner') ? 'selected' : '' ?>>Cleaner</option>
            <option value="reception" <?= (isset($_POST['role']) && $_POST['role'] == 'reception') ? 'selected' : '' ?>>Reception</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Register User</button>
</form>

<?php include 'footer.php'; ?>
