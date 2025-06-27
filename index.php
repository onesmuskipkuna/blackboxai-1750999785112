<?php
// index.php - Dashboard page

require_once 'db_connect.php';
require_once 'functions.php';

?>

<?php include 'header.php'; ?>

<h1 class="mb-4">Dashboard</h1>

<div class="row">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Cleanliness Reports</div>
            <div class="card-body">
                <?php
                // Count cleanliness reports
                $stmt = $pdo->query("SELECT COUNT(*) AS count FROM cleaning_reports");
                $cleanlinessCount = $stmt->fetch()['count'] ?? 0;
                echo "<h5 class='card-title'>{$cleanlinessCount}</h5>";
                ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Classes</div>
            <div class="card-body">
                <?php
                // Count classes
                $stmt = $pdo->query("SELECT COUNT(*) AS count FROM classes");
                $classesCount = $stmt->fetch()['count'] ?? 0;
                echo "<h5 class='card-title'>{$classesCount}</h5>";
                ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-header">Gym Areas</div>
            <div class="card-body">
                <?php
                // Count gym areas
                $stmt = $pdo->query("SELECT COUNT(*) AS count FROM gym_areas");
                $gymAreasCount = $stmt->fetch()['count'] ?? 0;
                echo "<h5 class='card-title'>{$gymAreasCount}</h5>";
                ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger mb-3">
            <div class="card-header">Maintenance Logs</div>
            <div class="card-body">
                <?php
                // Count maintenance logs
                $stmt = $pdo->query("SELECT COUNT(*) AS count FROM maintenance_logs");
                $maintenanceCount = $stmt->fetch()['count'] ?? 0;
                echo "<h5 class='card-title'>{$maintenanceCount}</h5>";
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mt-4">
    <div class="col-md-6">
        <canvas id="cleanlinessChart"></canvas>
    </div>
    <div class="col-md-6">
        <canvas id="classesChart"></canvas>
    </div>
</div>

<script>
    // Fetch data from PHP for charts
    const cleanlinessData = {
        labels: [
            <?php
            $stmt = $pdo->query("SELECT DISTINCT area FROM cleaning_reports ORDER BY area");
            $areas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo '"' . implode('","', $areas) . '"';
            ?>
        ],
        datasets: [{
            label: 'Average Cleanliness Rating',
            data: [
                <?php
                $ratings = [];
                foreach ($areas as $area) {
                    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM cleaning_reports WHERE area = ?");
                    $stmt->execute([$area]);
                    $avg = $stmt->fetchColumn();
                    $ratings[] = round($avg, 2);
                }
                echo implode(',', $ratings);
                ?>
            ],
            backgroundColor: 'rgba(54, 162, 235, 0.7)'
        }]
    };

    const classesData = {
        labels: [
            <?php
            $stmt = $pdo->query("SELECT DISTINCT attendance_date FROM class_attendance ORDER BY attendance_date");
            $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo '"' . implode('","', $dates) . '"';
            ?>
        ],
        datasets: [{
            label: 'Total Attendance',
            data: [
                <?php
                $attendanceCounts = [];
                foreach ($dates as $date) {
                    $stmt = $pdo->prepare("SELECT SUM(present_count) FROM class_attendance WHERE attendance_date = ?");
                    $stmt->execute([$date]);
                    $sum = $stmt->fetchColumn();
                    $attendanceCounts[] = $sum ?: 0;
                }
                echo implode(',', $attendanceCounts);
                ?>
            ],
            fill: false,
            borderColor: 'rgba(75, 192, 192, 1)',
            tension: 0.1
        }]
    };

    const cleanlinessCtx = document.getElementById('cleanlinessChart').getContext('2d');
    const cleanlinessChart = new Chart(cleanlinessCtx, {
        type: 'bar',
        data: cleanlinessData,
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, max: 5 }
            }
        }
    });

    const classesCtx = document.getElementById('classesChart').getContext('2d');
    const classesChart = new Chart(classesCtx, {
        type: 'line',
        data: classesData,
        options: {
            responsive: true
        }
    });
</script>

<?php include 'footer.php'; ?>
