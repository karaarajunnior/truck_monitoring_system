<?php
include 'config.php';
redirectIfNotAuthorized(['manager', 'admin']);

// Get date filter
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1>Manager Dashboard</h1>
            <nav>
                <a href="dashboard.php">Home</a>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <a href="admin.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <div class="manager-actions">
            <h2>Driver Logs for <?php echo date('F j, Y', strtotime($date_filter)); ?></h2>

            <form method="get" class="date-filter">
                <div class="form-group">
                    <label for="date">Select Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>">
                    <button type="submit" class="btn small">Filter</button>
                </div>
            </form>

            <div class="drivers-log-overview">
                <table>
                    <thead>
                        <tr>
                            <th>Driver Name</th>
                            <th>Location</th>
                            <th>Deliveries Made</th>
                            <th>Notes</th>
                            <th>Log Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT u.id, u.full_name FROM users u WHERE u.role = 'driver' ORDER BY u.full_name");
                        $drivers = $stmt->fetchAll();

                        foreach ($drivers as $driver) {
                            $log_stmt = $pdo->prepare("SELECT * FROM daily_logs WHERE driver_id = ? AND work_date = ?");
                            $log_stmt->execute([$driver['id'], $date_filter]);
                            $log = $log_stmt->fetch();

                            echo "<tr>";
                            echo "<td>{$driver['full_name']}</td>";

                            if ($log) {
                                echo "<td>{$log['location']}</td>";
                                echo "<td>{$log['deliveries_made']}</td>";
                                echo "<td>{$log['notes']}</td>";
                                echo "<td class='log-status submitted'>Submitted</td>";
                            } else {
                                echo "<td colspan='3' class='no-log'>No log submitted</td>";
                                echo "<td class='log-status missing'>Missing</td>";
                            }

                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Add this section to manager.php -->
    <div class="tracking-section">
        <h2>Live Driver Tracking</h2>
        <div class="map-container">
            <div id="live-map"></div>
        </div>
        <div class="tracking-controls">
            <select id="tracking-driver-select">
                <option value="">All Drivers</option>
                <?php
                $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'driver' ORDER BY full_name");
                while ($row = $stmt->fetch()) {
                    echo "<option value='{$row['id']}'>{$row['full_name']}</option>";
                }
                ?>
            </select>
            <select id="tracking-timeframe">
                <option value="hour">Last Hour</option>
                <option value="today" selected>Today</option>
                <option value="week">Last Week</option>
            </select>
            <button id="refresh-tracking" class="btn small">Refresh</button>
        </div>
    </div>

    <!-- Add these scripts -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

    <script>
        let map;
        let markers = [];
        let driverPaths = {};

        // Initialize map
        function initMap() {
            map = L.map('live-map').setView([0, 0], 2);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            refreshTrackingData();
        }

        // Fetch tracking data
        function refreshTrackingData() {
            const driverId = document.getElementById('tracking-driver-select').value;
            const timeframe = document.getElementById('tracking-timeframe').value;

            fetch(`tracking.php?driver_id=${driverId}&timeframe=${timeframe}`)
                .then(response => response.json())
                .then(data => {
                    updateMapWithData(data);
                });
        }

        // Update map with new data
        function updateMapWithData(locations) {
            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            driverPaths = {};

            if (locations.length === 0) {
                map.setView([0, 0], 2);
                return;
            }

            // Group locations by driver
            const locationsByDriver = {};
            locations.forEach(location => {
                if (!locationsByDriver[location.driver_id]) {
                    locationsByDriver[location.driver_id] = [];
                }
                locationsByDriver[location.driver_id].push(location);
            });

            // Process each driver's locations
            Object.keys(locationsByDriver).forEach(driverId => {
                const driverLocations = locationsByDriver[driverId];
                const driverName = driverLocations[0].full_name;

                // Sort by timestamp (oldest first)
                driverLocations.sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));

                // Create polyline for path
                const pathCoords = driverLocations.map(loc => [loc.latitude, loc.longitude]);
                const path = L.polyline(pathCoords, {
                    color: getRandomColor(driverId),
                    weight: 3,
                    opacity: 0.7
                }).addTo(map);

                driverPaths[driverId] = path;

                // Add markers for start and end points
                if (driverLocations.length > 0) {
                    const firstLoc = driverLocations[0];
                    const lastLoc = driverLocations[driverLocations.length - 1];

                    const startMarker = L.marker([firstLoc.latitude, firstLoc.longitude], {
                        icon: L.divIcon({
                            className: 'driver-marker start-marker',
                            html: `<div>${driverName} (Start)</div>`,
                            iconSize: [100, 20]
                        })
                    }).addTo(map);

                    const endMarker = L.marker([lastLoc.latitude, lastLoc.longitude], {
                        icon: L.divIcon({
                            className: 'driver-marker end-marker',
                            html: `<div>${driverName} (Current)</div>`,
                            iconSize: [100, 20]
                        })
                    }).addTo(map);

                    markers.push(startMarker, endMarker, path);
                }
            });

            // Fit map to show all paths
            const bounds = Object.values(driverPaths).reduce((acc, path) => {
                return acc.extend(path.getBounds());
            }, new L.LatLngBounds());

            map.fitBounds(bounds);
        }

        function getRandomColor(seed) {
            const colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c'];
            return colors[parseInt(seed) % colors.length];
        }

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', initMap);
        document.getElementById('refresh-tracking').addEventListener('click', refreshTrackingData);
        document.getElementById('tracking-driver-select').addEventListener('change', refreshTrackingData);
        document.getElementById('tracking-timeframe').addEventListener('change', refreshTrackingData);

        // Auto-refresh every 30 seconds
        setInterval(refreshTrackingData, 30000);
    </script>

</body>

</html>