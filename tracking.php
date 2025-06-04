<?php
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify driver is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
        exit;
    }

    $driver_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    // Check if data was properly received
    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
        exit;
    }

    // Validate and sanitize input
    if (!isset($data['latitude']) || !isset($data['longitude'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing coordinates']);
        exit;
    }

    $latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);
    $speed = isset($data['speed']) ? filter_var($data['speed'], FILTER_VALIDATE_FLOAT) : null;
    $accuracy = isset($data['accuracy']) ? filter_var($data['accuracy'], FILTER_VALIDATE_FLOAT) : null;

    if ($latitude === false || $longitude === false) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid coordinates']);
        exit;
    }

    // Check if active session exists
    $stmt = $pdo->prepare("SELECT id FROM driver_sessions WHERE driver_id = ? AND end_time IS NULL");
    $stmt->execute([$driver_id]);
    $session = $stmt->fetch();

    if (!$session) {
        // Start new session
        $stmt = $pdo->prepare("INSERT INTO driver_sessions (driver_id, start_time) VALUES (?, NOW())");
        $stmt->execute([$driver_id]);
        $session_id = $pdo->lastInsertId();
    } else {
        $session_id = $session['id'];
    }

    // Store location
    $stmt = $pdo->prepare("INSERT INTO driver_locations 
                          (driver_id, session_id, latitude, longitude, speed, accuracy, timestamp) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$driver_id, $session_id, $latitude, $longitude, $speed, $accuracy]);

    echo json_encode(['status' => 'success']);
    exit;
}

// For GET requests (viewing tracking data)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!function_exists('redirectIfNotAuthorized')) {
        function redirectIfNotAuthorized($allowed_roles)
        {
            if (
                !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
                !in_array($_SESSION['role'], $allowed_roles)
            ) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }
        }
    }

    redirectIfNotAuthorized(['admin', 'manager']);

    $driver_id = isset($_GET['driver_id']) ? filter_var($_GET['driver_id'], FILTER_VALIDATE_INT) : null;
    $timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'today';

    $query = "SELECT dl.*, u.full_name 
              FROM driver_locations dl
              JOIN users u ON dl.driver_id = u.id";

    $params = [];
    $whereAdded = false;

    if ($driver_id) {
        $query .= " WHERE dl.driver_id = ?";
        $params[] = $driver_id;
        $whereAdded = true;
    }

    switch ($timeframe) {
        case 'hour':
            $query .= ($whereAdded ? " AND" : " WHERE") . " dl.timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            break;
        case 'today':
            $query .= ($whereAdded ? " AND" : " WHERE") . " DATE(dl.timestamp) = CURDATE()";
            break;
        case 'week':
            $query .= ($whereAdded ? " AND" : " WHERE") . " dl.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
    }

    $query .= " ORDER BY dl.timestamp DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($locations);
    exit;
}

// Handle invalid request methods
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
exit;