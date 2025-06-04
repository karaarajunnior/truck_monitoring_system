<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'truck_monitoring_system';
$username = 'root'; // Change to your MySQL username

$password = ''; // Change to your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Test if connection is successful
    // echo "Connected successfully"; // Uncomment to test, then comment again
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn()
{
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

// Add to config.php
function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371; // km

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}
function redirectIfNotAuthorized($allowedRoles)
{
    redirectIfNotLoggedIn();
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: dashboard.php");
        exit();
    }
}
?>