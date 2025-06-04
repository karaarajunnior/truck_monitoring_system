<?php
include 'config.php';
redirectIfNotLoggedIn();

// Initialize variables safely
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
$driverId = isset($_GET['driver_id']) ? (int) $_GET['driver_id'] : 0;
$trackingDriverId = isset($_GET['tracking_driver']) ? (int) $_GET['tracking_driver'] : 0;

$months = [
    '01' => 'January',
    '02' => 'February',
    '03' => 'March',
    '04' => 'April',
    '05' => 'May',
    '06' => 'June',
    '07' => 'July',
    '08' => 'August',
    '09' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Reports</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1>Monthly Reports</h1>
            <nav>
                <a href="dashboard.php">Home</a>
                <?php if ($_SESSION['role'] == 'manager'): ?>
                    <a href="manager.php">Manager Dashboard</a>
                <?php elseif ($_SESSION['role'] == 'admin'): ?>
                    <a href="admin.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <div class="reports-section">
            <form method="get" action="">
                <div class="form-group">
                    <label for="month">Month:</label>
                    <select id="month" name="month" required>
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?php echo $num; ?>" <?php echo $month == $num ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year">Year:</label>
                    <input type="number" id="year" name="year" min="2000" max="<?php echo date('Y'); ?>"
                        value="<?php echo $year; ?>" required>
                </div>

                <?php if ($_SESSION['role'] != 'driver'): ?>
                    <div class="form-group">
                        <label for="driver_id">Driver (leave blank for all):</label>
                        <select id="driver_id" name="driver_id">
                            <option value="">-- All Drivers --</option>
                            <?php
                            $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'driver' ORDER BY full_name");
                            while ($row = $stmt->fetch()) {
                                $selected = $driverId == $row['id'] ? 'selected' : '';
                                echo "<option value=\"{$row['id']}\" $selected>{$row['full_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="driver_id" value="<?php echo $_SESSION['user_id']; ?>">
                <?php endif; ?>

                <button type="submit" class="btn">Generate Report</button>
            </form>

            <?php if ($_SESSION['role'] != 'driver'): ?>
                <div class="tracking-report-form">
                    <h2>Driver Movement Analysis</h2>
                    <form method="get" action="">
                        <input type="hidden" name="month" value="<?php echo $month; ?>">
                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                        <?php if (isset($_GET['driver_id'])): ?>
                            <input type="hidden" name="driver_id" value="<?php echo $driverId; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="tracking_driver">Driver:</label>
                            <select id="tracking_driver" name="tracking_driver" required>
                                <option value="">-- Select Driver --</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'driver' ORDER BY full_name");
                                while ($row = $stmt->fetch()) {
                                    $selected = $trackingDriverId == $row['id'] ? 'selected' : '';
                                    echo "<option value='{$row['id']}' $selected>{$row['full_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn">Generate Movement Report</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($trackingDriverId > 0): ?>
                <div class="tracking-report-results">
                    <h3>Movement Report for <?php
                    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$trackingDriverId]);
                    $driver = $stmt->fetch();
                    echo htmlspecialchars($driver['full_name']);
                    ?></h3>

                    <div class="map-container">
                        <div id="report-map"></div>
                    </div>

                    <?php
                    // Get tracking data for the selected driver and time period
                    $startDate = "$year-$month-01";
                    $endDate = date('Y-m-t', strtotime($startDate));

                    $stmt = $pdo->prepare("SELECT * FROM driver_locations 
                                        WHERE driver_id = ? 
                                        AND DATE(timestamp) BETWEEN ? AND ?
                                        ORDER BY timestamp");
                    $stmt->execute([$trackingDriverId, $startDate, $endDate]);
                    $locations = $stmt->fetchAll();

                    if (count($locations) > 0): ?>
                        <div class="tracking-stats">
                            <h4>Statistics</h4>
                            <div class="stats-grid">
                                <?php
                                // Calculate some basic stats
                                $firstLoc = $locations[0];
                                $lastLoc = end($locations);
                                $totalDistance = 0;
                                $totalTime = (strtotime($lastLoc['timestamp']) - strtotime($firstLoc['timestamp'])) / 3600; // in hours
                        
                                // Calculate approximate distance (simplified)
                                for ($i = 1; $i < count($locations); $i++) {
                                    $totalDistance += calculateDistance(
                                        $locations[$i - 1]['latitude'],
                                        $locations[$i - 1]['longitude'],
                                        $locations[$i]['latitude'],
                                        $locations[$i]['longitude']
                                    );
                                }
                                ?>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo count($locations); ?></div>
                                    <div class="stat-label">Location Points</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo round($totalDistance, 2); ?> km</div>
                                    <div class="stat-label">Total Distance</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo round($totalTime, 2); ?> hours</div>
                                    <div class="stat-label">Tracked Time</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">
                                        <?php echo $totalTime > 0 ? round($totalDistance / $totalTime, 2) : 0; ?> km/h
                                    </div>
                                    <div class="stat-label">Average Speed</div>
                                </div>
                            </div>
                        </div>

                        <h4>Location History</h4>
                        <table class="tracking-history">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Location</th>
                                    <th>Speed</th>
                                    <th>Accuracy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($locations as $loc): ?>
                                    <tr>
                                        <td><?php echo date('H:i:s', strtotime($loc['timestamp'])); ?></td>
                                        <td><?php echo round($loc['latitude'], 6); ?>, <?php echo round($loc['longitude'], 6); ?>
                                        </td>
                                        <td><?php echo $loc['speed'] ? round($loc['speed'] * 3.6, 2) . ' km/h' : 'N/A'; ?></td>
                                        <td><?php echo round($loc['accuracy']); ?> m</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No tracking data available for this period.</p>
                    <?php endif; ?>
                </div>

                <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
                <script>
                    // Initialize map for the report
                    document.addEventListener('DOMContentLoaded', function () {
                        const locations = <?php echo json_encode($locations); ?>;

                        if (locations.length > 0) {
                            const map = L.map('report-map').setView([locations[0].latitude, locations[0].longitude], 13);

                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                            }).addTo(map);

                            // Add path
                            const pathCoords = locations.map(loc => [loc.latitude, loc.longitude]);
                            L.polyline(pathCoords, { color: '#3498db', weight: 4 }).addTo(map);

                            // Add start and end markers
                            L.marker([locations[0].latitude, locations[0].longitude])
                                .bindPopup("Start: " + locations[0].timestamp)
                                .addTo(map);

                            if (locations.length > 1) {
                                L.marker([locations[locations.length - 1].latitude, locations[locations.length - 1].longitude])
                                    .bindPopup("End: " + locations[locations.length - 1].timestamp)
                                    .addTo(map);
                            }

                            // Fit map to show the entire path
                            map.fitBounds(L.polyline(pathCoords).getBounds());
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if (isset($_GET['month']) && isset($_GET['year'])): ?>
                <div class="report-results">
                    <h2>
                        <?php
                        $monthName = $months[$month];
                        if ($driverId) {
                            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                            $stmt->execute([$driverId]);
                            $driver = $stmt->fetch();
                            echo "Report for " . htmlspecialchars($driver['full_name']) . " - $monthName $year";
                        } else {
                            echo "Report for All Drivers - $monthName $year";
                        }
                        ?>
                    </h2>

                    <?php
                    $startDate = "$year-$month-01";
                    $endDate = date('Y-m-t', strtotime($startDate));

                    $query = "SELECT l.*, u.full_name 
                                  FROM daily_logs l 
                                  JOIN users u ON l.driver_id = u.id 
                                  WHERE l.work_date BETWEEN ? AND ?";

                    $params = [$startDate, $endDate];

                    if ($driverId) {
                        $query .= " AND l.driver_id = ?";
                        $params[] = $driverId;
                    }

                    $query .= " ORDER BY u.full_name, l.work_date";

                    $stmt = $pdo->prepare($query);
                    $stmt->execute($params);
                    $logs = $stmt->fetchAll();

                    if ($logs):
                        $currentDriver = null;
                        foreach ($logs as $log):
                            if ($log['driver_id'] != $currentDriver):
                                if ($currentDriver !== null) {
                                    echo "</table></div>";
                                }
                                $currentDriver = $log['driver_id'];
                                echo "<div class='driver-report'>
                                          <h3>" . htmlspecialchars($log['full_name']) . "</h3>
                                          <table>
                                              <thead>
                                                  <tr>
                                                      <th>Date</th>
                                                      <th>Location</th>
                                                      <th>Deliveries Made</th>
                                                      <th>Notes</th>
                                                  </tr>
                                              </thead>
                                              <tbody>";
                            endif;

                            echo "<tr>
                                      <td>" . date('M j, Y', strtotime($log['work_date'])) . "</td>
                                      <td>" . htmlspecialchars($log['location']) . "</td>
                                      <td>" . htmlspecialchars($log['deliveries_made']) . "</td>
                                      <td>" . htmlspecialchars($log['notes']) . "</td>
                                  </tr>";
                        endforeach;
                        echo "</tbody></table></div>";
                    else:
                        echo "<p>No logs found for the selected period.</p>";
                    endif;
                    ?>

                    <div class="report-actions">
                        <button onclick="window.print()" class="btn">Print Report</button>
                        <button onclick="exportToExcel()" class="btn">Export to Excel</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function exportToExcel() {
            let html = document.querySelector('.report-results').outerHTML;
            let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            let a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'truck_report_<?php echo $months[$month] ?? ''; ?>_<?php echo $year; ?>.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>

</html>