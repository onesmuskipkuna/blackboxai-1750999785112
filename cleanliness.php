<?php
require_once 'db_connect.php';
require_once 'functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cleaner_name = sanitizeInput($_POST['cleaner_name'] ?? '');
    $cleaning_area = sanitizeInput($_POST['cleaning_area'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $remarks = sanitizeInput($_POST['remarks'] ?? '');

    if (!$cleaner_name) {
        $errors[] = 'Cleaner name is required.';
    }
    if (!$cleaning_area) {
        $errors[] = 'Cleaning area is required.';
    }
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating must be between 1 and 5.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert cleaner if not exists
            $stmt = $pdo->prepare("SELECT id FROM cleaners WHERE name = ?");
            $stmt->execute([$cleaner_name]);
            $cleaner = $stmt->fetch();

            if (!$cleaner) {
                $stmt = $pdo->prepare("INSERT INTO cleaners (name, cleaning_area, rating, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$cleaner_name, $cleaning_area, $rating]);
                $cleaner_id = $pdo->lastInsertId();
            } else {
                $cleaner_id = $cleaner['id'];
                // Update cleaner rating and area
                $stmt = $pdo->prepare("UPDATE cleaners SET cleaning_area = ?, rating = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$cleaning_area, $rating, $cleaner_id]);
            }

            // Insert cleaning report
            $stmt = $pdo->prepare("INSERT INTO cleaning_reports (cleaner_id, area, rating, report_date, remarks) VALUES (?, ?, ?, NOW(), ?)");
            $stmt->execute([$cleaner_id, $cleaning_area, $rating, $remarks]);

            $pdo->commit();
            $success = 'Cleaning report added successfully.';
        } catch (Exception $e) {
            $pdo->rollBack();
            logError("Error adding cleaning report: " . $e->getMessage());
            $errors[] = 'Failed to add cleaning report. Please try again.';
        }
    }
}

// Fetch cleaning reports
$stmt = $pdo->query("SELECT cr.id, c.name AS cleaner_name, cr.area, cr.rating, cr.report_date, cr.remarks FROM cleaning_reports cr JOIN cleaners c ON cr.cleaner_id = c.id ORDER BY cr.report_date DESC");
$reports = $stmt->fetchAll();

include 'header.php';
?>

<h1>Cleanliness Management</h1>

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
        <label for="cleaner_name" class="form-label">Cleaner Name</label>
        <input type="text" class="form-control" id="cleaner_name" name="cleaner_name" required value="<?= htmlspecialchars($_POST['cleaner_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="cleaning_area" class="form-label">Cleaning Area</label>
        <input type="text" class="form-control" id="cleaning_area" name="cleaning_area" required value="<?= htmlspecialchars($_POST['cleaning_area'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="rating" class="form-label">Rating (1 to 5)</label>
        <select class="form-select" id="rating" name="rating" required>
            <option value="">Select rating</option>
            <?php for ($i=1; $i<=5; $i++): ?>
            <option value="<?= $i ?>" <?= (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="remarks" class="form-label">Remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="3"><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Add Cleaning Report</button>
</form>

<h2>Cleaning Reports</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Cleaner Name</th>
            <th>Area</th>
            <th>Rating</th>
            <th>Report Date</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($reports): ?>
            <?php foreach ($reports as $report): ?>
            <tr>
                <td><?= htmlspecialchars($report['id']) ?></td>
                <td><?= htmlspecialchars($report['cleaner_name']) ?></td>
                <td><?= htmlspecialchars($report['area']) ?></td>
                <td><?= htmlspecialchars($report['rating']) ?></td>
                <td><?= htmlspecialchars($report['report_date']) ?></td>
                <td><?= htmlspecialchars($report['remarks']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No reports found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>
