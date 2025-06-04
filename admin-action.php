<?php
include 'config.php';
redirectIfNotAuthorized(['admin']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    // Add user
    if ($action == 'add') {
        $full_name = $_POST['full_name'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $license_number = $_POST['license_number'];
        $phone = $_POST['phone'];
        $role = $_POST['role'];

        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, license_number, phone, role) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $username, $password, $license_number, $phone, $role]);

        header("Location: admin.php?success=added");
        exit();
    }

    // Edit user
    if ($action == 'edit') {
        $id = $_POST['id'];
        $full_name = $_POST['full_name'];
        $username = $_POST['username'];
        $license_number = $_POST['license_number'];
        $phone = $_POST['phone'];
        $role = $_POST['role'];

        if (!empty($_POST['password'])) {
            // Update with new password
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, password = ?, 
                                  license_number = ?, phone = ?, role = ? WHERE id = ?");
            $stmt->execute([$full_name, $username, $password, $license_number, $phone, $role, $id]);
        } else {
            // Keep existing password
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, 
                                  license_number = ?, phone = ?, role = ? WHERE id = ?");
            $stmt->execute([$full_name, $username, $license_number, $phone, $role, $id]);
        }

        header("Location: admin.php?success=updated");
        exit();
    }
}

// Handle delete action from GET request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Don't allow deleting your own account
    if ($id == $_SESSION['user_id']) {
        header("Location: admin.php?error=cannot_delete_self");
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: admin.php?success=deleted");
    exit();
}

// Redirect back if no valid action was found
header("Location: admin.php");
exit();
?>