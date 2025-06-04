<?php
include 'config.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
    } else {
        header("Location: index.php?error=1");
        exit();
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['full_name'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $license_number = $_POST['license_number'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, license_number, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$full_name, $username, $password, $license_number, $phone, $role]);

    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['full_name'] = $full_name;
}

redirectIfNotLoggedIn();



// Handle daily log submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['location'])) {
    $driver_id = $_SESSION['user_id'];
    $work_date = date('Y-m-d');
    $location = $_POST['location'];
    $deliveries = $_POST['deliveries'];
    $notes = $_POST['notes'];

    $stmt = $pdo->prepare("INSERT INTO daily_logs (driver_id, work_date, location, deliveries_made, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$driver_id, $work_date, $location, $deliveries, $notes]);

    $success = "Daily log submitted successfully!";
}

// Get today's log if exists
$today_log = null;
if ($_SESSION['role'] == 'driver') {
    $stmt = $pdo->prepare("SELECT * FROM daily_logs WHERE driver_id = ? AND work_date = ?");
    $stmt->execute([$_SESSION['user_id'], date('Y-m-d')]);
    $today_log = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, <?php echo $_SESSION['full_name']; ?></h1>
            <nav>
                <?php if ($_SESSION['role'] == 'manager'): ?>
                    <a href="manager.php">Manager Dashboard</a>
                <?php elseif ($_SESSION['role'] == 'admin'): ?>
                    <a href="admin.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <?php if ($_SESSION['role'] == 'driver'): ?>
            <div class="daily-log">
                <h2>Daily Work Log</h2>
                <?php if (isset($success)): ?>
                    <p class="success"><?php echo $success; ?></p>
                <?php endif; ?>

                <?php if ($today_log): ?>
                    <div class="log-display">
                        <h3>Today's Log (<?php echo date('F j, Y'); ?>)</h3>
                        <p><strong>Location:</strong> <?php echo $today_log['location']; ?></p>
                        <p><strong>Deliveries Made:</strong> <?php echo $today_log['deliveries_made']; ?></p>
                        <p><strong>Notes:</strong> <?php echo $today_log['notes']; ?></p>
                    </div>
                <?php else: ?>
                    <form method="post">
                        <div class="form-group">
                            <label for="location">Today's Location:</label>
                            <input type="text" id="location" name="location" required>
                        </div>
                        <div class="form-group">
                            <label for="deliveries">Deliveries Made:</label>
                            <textarea id="deliveries" name="deliveries" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="notes">Additional Notes:</label>
                            <textarea id="notes" name="notes"></textarea>
                        </div>
                        <button type="submit" class="btn">Submit Daily Log</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add this to dashboard.php where driver controls are shown -->
    <div class="tracking-widget">
        <h3>Location Tracking</h3>
        <div class="driver-status">
            <div id="tracking-status" class="status-indicator status-inactive"></div>
            <span>Tracking Status: <span id="status-text">Inactive</span></span>
        </div>
        <p>Last update: <span id="last-update">Never</span></p>
        <button id="toggle-tracking" class="btn">Start Tracking</button>
    </div>

    <!-- Include the tracking script -->
    <script src="/driver-tracking.js"></script>
</body>

</html>