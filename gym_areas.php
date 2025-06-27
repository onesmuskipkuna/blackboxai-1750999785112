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
    $area_name = sanitizeInput($_POST['area_name'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $trainer_id = intval($_POST['trainer_id'] ?? 0);
    $timetable = sanitizeInput($_POST['timetable'] ?? '');
    $performance_rating = intval($_POST['performance_rating'] ?? 0);

    if (!$area_name) {
        $errors[] = 'Gym area name is required.';
    }
    if (!in_array($category, ['main', 'ladies'])) {
        $errors[] = 'Please select a valid category.';
    }
    if ($trainer_id <= 0) {
        $errors[] = 'Please select a trainer.';
    }
    if (!$timetable) {
        $errors[] = 'Timetable is required.';
    }
    if ($performance_rating < 1 || $performance_rating > 5) {
        $errors[] = 'Performance rating must be between 1 and 5.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO gym_areas (name, category, assigned_trainer_id, timetable, performance_rating) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$area_name, $category, $trainer_id, $timetable, $performance_rating]);
            $success = 'Gym area assigned and rated successfully.';
        } catch (Exception $e) {
            logError("Error adding gym area: " . $e->getMessage());
            $errors[] = 'Failed to add gym area. Please try again.';
        }
    }
}

// Fetch gym areas with trainer names
$stmt = $pdo->query("SELECT ga.id, ga.name, ga.category, st.name AS trainer_name, ga.timetable, ga.performance_rating FROM gym_areas ga LEFT JOIN staff st ON ga.assigned_trainer_id = st.id ORDER BY ga.id DESC");
$areas = $stmt->fetchAll();

include 'header.php';
?>

<h1>Gym Area Management</h1>

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
        <label for="area_name" class="form-label">Gym Area Name</label>
        <input type="text" class="form-control" id="area_name" name="area_name" required value="<?= htmlspecialchars($_POST['area_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="category" class="form-label">Category</label>
        <select class="form-select" id="category" name="category" required>
            <option value="">Select Category</option>
            <option value="main" <?= (isset($_POST['category']) && $_POST['category'] == 'main') ? 'selected' : '' ?>>Main Gym Area</option>
            <option value="ladies" <?= (isset($_POST['category']) && $_POST['category'] == 'ladies') ? 'selected' : '' ?>>Ladies Gym Area</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="trainer_id" class="form-label">Assign Trainer</label>
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
        <label for="performance_rating" class="form-label">Performance Rating (1 to 5)</label>
        <select class="form-select" id="performance_rating" name="performance_rating" required>
            <option value="">Select Rating</option>
            <?php for ($i=1; $i<=5; $i++): ?>
            <option value="<?= $i ?>" <?= (isset($_POST['performance_rating']) && $_POST['performance_rating'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Add Gym Area</button>
</form>

<h2>Gym Areas</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Area Name</th>
            <th>Category</th>
            <th>Trainer</th>
            <th>Timetable</th>
            <th>Performance Rating</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($areas): ?>
            <?php foreach ($areas as $area): ?>
            <tr>
                <td><?= htmlspecialchars($area['id']) ?></td>
                <td><?= htmlspecialchars($area['name']) ?></td>
                <td><?= htmlspecialchars(ucfirst($area['category'])) ?></td>
                <td><?= htmlspecialchars($area['trainer_name']) ?></td>
                <td><?= htmlspecialchars($area['timetable']) ?></td>
                <td><?= htmlspecialchars($area['performance_rating']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No gym areas found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>
