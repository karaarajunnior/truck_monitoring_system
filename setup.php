<?php
include 'config.php';

// This file should be deleted after initial setup for security

// Check if setup has already been completed
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$user_count = $stmt->fetch()['count'];

if ($user_count > 0) {
    echo "<h2>Setup has already been completed!</h2>";
    echo "<p>For security reasons, please delete this file.</p>";
    echo "<p><a href='index.php'>Go to login page</a></p>";
    exit();
}

// Create initial admin user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_admin'])) {
    $admin_name = $_POST['admin_name'];
    $admin_username = $_POST['admin_username'];
    $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
    $admin_license = $_POST['admin_license'];
    $admin_phone = $_POST['admin_phone'];

    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, license_number, phone, role) 
                          VALUES (?, ?, ?, ?, ?, 'admin')");
    $stmt->execute([$admin_name, $admin_username, $admin_password, $admin_license, $admin_phone]);

    // Create manager
    $manager_name = $_POST['manager_name'];
    $manager_username = $_POST['manager_username'];
    $manager_password = password_hash($_POST['manager_password'], PASSWORD_DEFAULT);
    $manager_license = $_POST['manager_license'];
    $manager_phone = $_POST['manager_phone'];

    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, license_number, phone, role) 
                          VALUES (?, ?, ?, ?, ?, 'manager')");
    $stmt->execute([$manager_name, $manager_username, $manager_password, $manager_license, $manager_phone]);

    // Create driver
    $driver_name = $_POST['driver_name'];
    $driver_username = $_POST['driver_username'];
    $driver_password = password_hash($_POST['driver_password'], PASSWORD_DEFAULT);
    $driver_license = $_POST['driver_license'];
    $driver_phone = $_POST['driver_phone'];


    $pdo->exec("CREATE TABLE IF NOT EXISTS driver_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    speed FLOAT,
    accuracy FLOAT,
    FOREIGN KEY (driver_id) REFERENCES users(id)
)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS driver_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME,
    FOREIGN KEY (driver_id) REFERENCES users(id)
)");

    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, license_number, phone, role) 
                          VALUES (?, ?, ?, ?, ?, 'driver')");
    $stmt->execute([$driver_name, $driver_username, $driver_password, $driver_license, $driver_phone]);

    echo "<h2>Setup completed successfully!</h2>";
    echo "<p>Initial users have been created.</p>";
    echo "<p><strong>Admin:</strong> " . $admin_username . "</p>";
    echo "<p><strong>Manager:</strong> " . $manager_username . "</p>";
    echo "<p><strong>Driver:</strong> " . $driver_username . "</p>";
    echo "<p>For security reasons, please delete this file.</p>";
    echo "<p><a href='index.php'>Go to login page</a></p>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .user-section {
            margin-bottom: 2rem;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        h1,
        h2 {
            color: #333;
        }

        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <div class="setup-container">
        <h1>Truck Monitoring System - Initial Setup</h1>

        <div class="warning">
            <strong>Warning:</strong> This page should only be used for initial system setup. Delete this file after
            setup is complete.
        </div>

        <p>This page will create initial admin, manager, and driver accounts for the system.</p>

        <form method="post">
            <div class="user-section">
                <h2>Admin User</h2>
                <div class="form-group">
                    <label for="admin_name">Full Name:</label>
                    <input type="text" id="admin_name" name="admin_name" required>
                </div>
                <div class="form-group">
                    <label for="admin_username">Username:</label>
                    <input type="text" id="admin_username" name="admin_username" required>
                </div>
                <div class="form-group">
                    <label for="admin_password">Password:</label>
                    <input type="password" id="admin_password" name="admin_password" required>
                </div>
                <div class="form-group">
                    <label for="admin_license">License Number:</label>
                    <input type="text" id="admin_license" name="admin_license" required>
                </div>
                <div class="form-group">
                    <label for="admin_phone">Phone Number:</label>
                    <input type="tel" id="admin_phone" name="admin_phone" required>
                </div>
            </div>

            <div class="user-section">
                <h2>Manager User</h2>
                <div class="form-group">
                    <label for="manager_name">Full Name:</label>
                    <input type="text" id="manager_name" name="manager_name" required>
                </div>
                <div class="form-group">
                    <label for="manager_username">Username:</label>
                    <input type="text" id="manager_username" name="manager_username" required>
                </div>
                <div class="form-group">
                    <label for="manager_password">Password:</label>
                    <input type="password" id="manager_password" name="manager_password" required>
                </div>
                <div class="form-group">
                    <label for="manager_license">License Number:</label>
                    <input type="text" id="manager_license" name="manager_license" required>
                </div>
                <div class="form-group">
                    <label for="manager_phone">Phone Number:</label>
                    <input type="tel" id="manager_phone" name="manager_phone" required>
                </div>
            </div>

            <div class="user-section">
                <h2>Driver User</h2>
                <div class="form-group">
                    <label for="driver_name">Full Name:</label>
                    <input type="text" id="driver_name" name="driver_name" required>
                </div>
                <div class="form-group">
                    <label for="driver_username">Username:</label>
                    <input type="text" id="driver_username" name="driver_username" required>
                </div>
                <div class="form-group">
                    <label for="driver_password">Password:</label>
                    <input type="password" id="driver_password" name="driver_password" required>
                </div>
                <div class="form-group">
                    <label for="driver_license">License Number:</label>
                    <input type="text" id="driver_license" name="driver_license" required>
                </div>
                <div class="form-group">
                    <label for="driver_phone">Phone Number:</label>
                    <input type="tel" id="driver_phone" name="driver_phone" required>
                </div>
            </div>

            <button type="submit" name="create_admin" class="btn">Complete Setup</button>
        </form>
    </div>
</body>

</html>