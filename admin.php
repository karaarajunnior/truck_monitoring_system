<?php
include 'config.php';
redirectIfNotAuthorized(['admin']);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1>Admin Dashboard</h1>
            <nav>
                <a href="dashboard.php">Home</a>
                <a href="manager.php">Manager View</a>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <div class="admin-actions">
            <h2>Manage Drivers</h2>

            <div class="tabs">
                <button class="tab-btn active" onclick="openTab('view')">View Drivers</button>
                <button class="tab-btn" onclick="openTab('add')">Add Driver</button>
                <button class="tab-btn" onclick="openTab('edit')">Edit Driver</button>
            </div>

            <div id="view" class="tab-content active">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>License Number</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM users ORDER BY role, full_name");
                        while ($row = $stmt->fetch()) {
                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>{$row['full_name']}</td>";
                            echo "<td>{$row['username']}</td>";
                            echo "<td>{$row['license_number']}</td>";
                            echo "<td>{$row['phone']}</td>";
                            echo "<td>{$row['role']}</td>";
                            echo "<td>
                                    <a href='admin.php?edit={$row['id']}' class='btn small'>Edit</a>
                                    <a href='admin.php?delete={$row['id']}' class='btn small danger' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div id="add" class="tab-content">
                <h3>Add New Driver</h3>
                <form method="post" action="admin-action.php">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="license_number">License Number:</label>
                        <input type="text" id="license_number" name="license_number" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role" required>
                            <option value="driver">Driver</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Add User</button>
                </form>
            </div>

            <?php if (isset($_GET['edit'])): ?>
                <div id="edit" class="tab-content active">
                    <h3>Edit Driver</h3>
                    <?php
                    $id = $_GET['edit'];
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $user = $stmt->fetch();

                    if ($user):
                        ?>
                        <form method="post" action="admin-action.php">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                            <div class="form-group">
                                <label for="full_name">Full Name:</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo $user['full_name']; ?>"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="username">Username:</label>
                                <input type="text" id="username" name="username" value="<?php echo $user['username']; ?>"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="password">New Password (leave blank to keep current):</label>
                                <input type="password" id="password" name="password">
                            </div>
                            <div class="form-group">
                                <label for="license_number">License Number:</label>
                                <input type="text" id="license_number" name="license_number"
                                    value="<?php echo $user['license_number']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number:</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="role">Role:</label>
                                <select id="role" name="role" required>
                                    <option value="driver" <?php echo $user['role'] == 'driver' ? 'selected' : ''; ?>>Driver
                                    </option>
                                    <option value="manager" <?php echo $user['role'] == 'manager' ? 'selected' : ''; ?>>Manager
                                    </option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="btn">Update User</button>
                        </form>
                    <?php else: ?>
                        <p>User not found.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            const tabButtons = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }

            document.getElementById(tabName).classList.add("active");

            // Find the button that corresponds to this tab and mark it active
            const buttons = document.querySelectorAll('.tab-btn');
            for (let i = 0; i < buttons.length; i++) {
                if (buttons[i].getAttribute('onclick').includes(tabName)) {
                    buttons[i].classList.add("active");
                }
            }
        }

        // Ensure the edit tab is opened when edit parameter is present
        window.onload = function () {
            <?php if (isset($_GET['edit'])): ?>
                openTab('edit');
            <?php endif; ?>
        }
    </script>
</body>

</html>