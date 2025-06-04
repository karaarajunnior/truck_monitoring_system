<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $full_name = $_POST['full_name'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $license_number = $_POST['license_number'];
        $phone = $_POST['phone'];

        // Check if this is the first user - make them admin
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        $role = ($count == 0) ? 'admin' : 'driver'; // First user becomes admin

        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, license_number, phone, role) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $username, $password, $license_number, $phone, $role]);

        // Show success message with role information
        $success_message = "Registration successful! ";
        $success_message .= ($role == 'admin') ? "You have been registered as the system administrator." : "You have been registered as a driver.";

        // Redirect to login page after successful registration
        header("Location: index.php?registration=success&message=" . urlencode($success_message));
        exit();
    } catch (PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Registration</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Google Fonts for a modern look -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="register-background">
        <div class="register-container">
            <div class="register-image">
                <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=600&q=80"
                    alt="Truck Registration">
            </div>
            <div class="register-form-section">
                <h1>Driver Registration</h1>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <form method="post" action="register.php">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="license_number">License Number</label>
                        <input type="text" id="license_number" name="license_number" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <button type="submit" class="btn">Register</button>
                </form>
                <p class="login-link">Already have an account? <a href="index.php">Login</a></p>
            </div>
        </div>
    </div>
</body>

</html>