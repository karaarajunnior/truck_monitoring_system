<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truck Monitoring System - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Google Fonts for a modern look -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="login-background">
        <div class="login-container">
            <div class="login-image">
                <img src="https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=600&q=80"
                    alt="Truck Image">
            </div>
            <div class="login-form-section">
                <h1>Truck Monitoring System</h1>
                <form action="dashboard.php" method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
                <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
                <?php if (isset($_GET['error'])): ?>
                    <p class="error">Invalid username or password</p>
                <?php endif; ?>
                <?php if (isset($_GET['registration']) && $_GET['registration'] == 'success'): ?>
                    <p class="success">
                        <?php echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Registration successful!'; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>