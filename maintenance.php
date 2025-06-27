<?php
require_once 'db_connect.php';
require_once 'functions.php';

$errors = [];
$success = '';

// Fetch machine categories for dropdown
$stmt = $pdo->query("SELECT id, category_name FROM maintenance_categories ORDER BY category_name ASC");
$categories = $stmt->fetchAll();

// Fetch maintenance staff for notifications (role 'maintenance')
$stmt2 = $pdo->prepare("SELECT id, name, contact_email, contact_phone FROM staff WHERE role = 'maintenance'");
$stmt2->execute();
$maintenance_staff = $stmt2->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $machine_name = sanitizeInput($_POST['machine_name'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? '');
    $remarks = sanitizeInput($_POST['remarks'] ?? '');
    $updated_by = sanitizeInput($_POST['updated_by'] ?? '');

    if ($category_id <= 0) {
        $errors[] = 'Please select a machine category.';
    }
    if (!$machine_name) {
        $errors[] = 'Machine name is required.';
    }
    if (!in_array($status, ['good condition', 'fault', 'needs repair'])) {
        $errors[] = 'Please select a valid status.';
    }
    if (!$updated_by) {
        $errors[] = 'Updated by is required.';
    }

    if (empty($errors)) {
        try {
            // Check if machine exists
            $stmt = $pdo->prepare("SELECT id, status FROM machines WHERE name = ? AND category_id = ?");
            $stmt->execute([$machine_name, $category_id]);
            $machine = $stmt->fetch();

            if (!$machine) {
                // Insert new machine
                $stmt = $pdo->prepare("INSERT INTO machines (name, category_id, status, last_updated, remarks) VALUES (?, ?, ?, NOW(), ?)");
                $stmt->execute([$machine_name, $category_id, $status, $remarks]);
                $machine_id = $pdo->lastInsertId();
                $previous_status = null;
            } else {
                $machine_id = $machine['id'];
                $previous_status = $machine['status'];
                // Update machine status and remarks
                $stmt = $pdo->prepare("UPDATE machines SET status = ?, last_updated = NOW(), remarks = ? WHERE id = ?");
                $stmt->execute([$status, $remarks, $machine_id]);
            }

            // Insert maintenance log
            $stmt = $pdo->prepare("INSERT INTO maintenance_logs (machine_id, previous_status, new_status, remarks, performed_by, log_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$machine_id, $previous_status, $status, $remarks, $updated_by]);

            // Send notifications to maintenance staff if status changed
            if ($previous_status !== $status) {
                $subject = "Machine Status Update: $machine_name";
                $message = "The status of machine '$machine_name' has been updated from '$previous_status' to '$status'. Remarks: $remarks";

                foreach ($maintenance_staff as $staff) {
                    if ($staff['contact_email']) {
                        sendEmail($staff['contact_email'], $subject, $message);
                    }
                    if ($staff['contact_phone']) {
                        sendSMS($staff['contact_phone'], $message);
                    }
                }
            }

            $success = 'Machine status updated and notifications sent.';
        } catch (Exception $e) {
            logError("Error updating machine status: " . $e->getMessage());
            $errors[] = 'Failed to update machine status. Please try again.';
        }
    }
}

// Fetch machines with category names
$stmt = $pdo->query("SELECT m.id, m.name, mc.category_name, m.status, m.last_updated, m.remarks FROM machines m LEFT JOIN maintenance_categories mc ON m.category_id = mc.id ORDER BY m.last_updated DESC");
$machines = $stmt->fetchAll();

include 'header.php';
?>

<h1>Maintenance Management</h1>

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
        <label for="category_id" class="form-label">Machine Category</label>
        <select class="form-select" id="category_id" name="category_id" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['category_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="machine_name" class="form-label">Machine Name</label>
        <input type="text" class="form-control" id="machine_name" name="machine_name" required value="<?= htmlspecialchars($_POST['machine_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status" required>
            <option value="">Select Status</option>
            <option value="good condition" <?= (isset($_POST['status']) && $_POST['status'] == 'good condition') ? 'selected' : '' ?>>Good Condition</option>
            <option value="fault" <?= (isset($_POST['status']) && $_POST['status'] == 'fault') ? 'selected' : '' ?>>Fault</option>
            <option value="needs repair" <?= (isset($_POST['status']) && $_POST['status'] == 'needs repair') ? 'selected' : '' ?>>Needs Repair</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="remarks" class="form-label">Remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="3"><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label for="updated_by" class="form-label">Updated By</label>
        <input type="text" class="form-control" id="updated_by" name="updated_by" required value="<?= htmlspecialchars($_POST['updated_by'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-primary">Update Machine Status</button>
</form>

<h2>Machines</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Machine Name</th>
            <th>Category</th>
            <th>Status</th>
            <th>Last Updated</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($machines): ?>
            <?php foreach ($machines as $machine): ?>
            <tr>
                <td><?= htmlspecialchars($machine['id']) ?></td>
                <td><?= htmlspecialchars($machine['name']) ?></td>
                <td><?= htmlspecialchars($machine['category_name']) ?></td>
                <td><?= htmlspecialchars($machine['status']) ?></td>
                <td><?= htmlspecialchars($machine['last_updated']) ?></td>
                <td><?= htmlspecialchars($machine['remarks']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No machines found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>
