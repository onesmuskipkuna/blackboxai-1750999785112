<?php
require_once 'db_connect.php';
require_once 'functions.php';

$errors = [];
$success = '';

// Fetch trainers for dropdown (staff with role 'trainer')
$stmt = $pdo->prepare("SELECT id, name FROM staff WHERE role = 'trainer'");
$stmt->execute();
$trainers = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = sanitizeInput($_POST['class_name'] ?? '');
    $trainer_id = intval($_POST['trainer_id'] ?? 0);
    $timetable = sanitizeInput($_POST['timetable'] ?? '');
    $attendance_date = sanitizeInput($_POST['attendance_date'] ?? '');
    $present_count = intval($_POST['present_count'] ?? 0);
    $remarks = sanitizeInput($_POST['remarks'] ?? '');

    if (!$class_name) {
        $errors[] = 'Class name is required.';
    }
    if ($trainer_id <= 0) {
        $errors[] = 'Please select a trainer.';
    }
    if (!$timetable) {
        $errors[] = 'Timetable is required.';
    }
    if (!$attendance_date) {
        $errors[] = 'Attendance date is required.';
    }
    if ($present_count < 0) {
        $errors[] = 'Present count cannot be negative.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert or update class
            $stmt = $pdo->prepare("SELECT id FROM classes WHERE class_name = ?");
            $stmt->execute([$class_name]);
            $class = $stmt->fetch();

            if (!$class) {
                $stmt = $pdo->prepare("INSERT INTO classes (class_name, trainer_id, timetable, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$class_name, $trainer_id, $timetable]);
                $class_id = $pdo->lastInsertId();
            } else {
                $class_id = $class['id'];
                $stmt = $pdo->prepare("UPDATE classes SET trainer_id = ?, timetable = ? WHERE id = ?");
                $stmt->execute([$trainer_id, $timetable, $class_id]);
            }

            // Insert attendance
            $stmt = $pdo->prepare("INSERT INTO class_attendance (class_id, attendance_date, present_count, remarks) VALUES (?, ?, ?, ?)");
            $stmt->execute([$class_id, $attendance_date, $present_count, $remarks]);

            $pdo->commit();
            $success = 'Class and attendance recorded successfully.';
        } catch (Exception $e) {
            $pdo->rollBack();
            logError("Error adding class or attendance: " . $e->getMessage());
            $errors[] = 'Failed to record class or attendance. Please try again.';
        }
    }
}

// Fetch classes with trainer names
$stmt = $pdo->query("SELECT cl.id, cl.class_name, st.name AS trainer_name, cl.timetable, cl.created_at FROM classes cl LEFT JOIN staff st ON cl.trainer_id = st.id ORDER BY cl.created_at DESC");
$classes = $stmt->fetchAll();

include 'header.php';
?>

<h1>Classes & Timetable Management</h1>

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
        <label for="class_name" class="form-label">Class Name</label>
        <input type="text" class="form-control" id="class_name" name="class_name" required value="<?= htmlspecialchars($_POST['class_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="trainer_id" class="form-label">Trainer</label>
        <select class="form-select" id="trainer_id" name="trainer_id" required>
            <option value="">Select Trainer</option>
            <?php foreach ($trainers as $trainer): ?>
            <option value="<?= $trainer['id'] ?>" <?= (isset($_POST['trainer_id']) && $_POST['trainer_id'] == $trainer['id']) ? 'selected' : '' ?>><?= htmlspecialchars($trainer['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="timetable" class="form-label">Timetable (e.g., Mon 9am-10am)</label>
        <input type="text" class="form-control" id="timetable" name="timetable" required value="<?= htmlspecialchars($_POST['timetable'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="attendance_date" class="form-label">Attendance Date</label>
        <input type="date" class="form-control" id="attendance_date" name="attendance_date" required value="<?= htmlspecialchars($_POST['attendance_date'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="present_count" class="form-label">Present Count</label>
        <input type="number" class="form-control" id="present_count" name="present_count" min="0" required value="<?= htmlspecialchars($_POST['present_count'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="remarks" class="form-label">Remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="3"><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Add Class & Attendance</button>
</form>

<h2>Classes</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Class Name</th>
            <th>Trainer</th>
            <th>Timetable</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($classes): ?>
            <?php foreach ($classes as $class): ?>
            <tr>
                <td><?= htmlspecialchars($class['id']) ?></td>
                <td><?= htmlspecialchars($class['class_name']) ?></td>
                <td><?= htmlspecialchars($class['trainer_name']) ?></td>
                <td><?= htmlspecialchars($class['timetable']) ?></td>
                <td><?= htmlspecialchars($class['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No classes found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>
