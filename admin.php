<?php
session_start();


$host = 'localhost';
$dbname = 'hotel_booking';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


$message = "";


$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;


if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: admin.php");
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username");
    $stmt->execute([':username' => $inputUsername]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        
        if (password_verify($inputPassword, $admin['password'])) {
            
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: admin.php");
            exit;
        } else {
            
            $message = "Invalid username or password.";
        }
    } else {
       
        $message = "Invalid username or password.";
    }
}

if (!$isAdmin && basename($_SERVER['PHP_SELF']) !== 'admin.php') {
    header("Location: admin.php");
    exit;
}

if ($isAdmin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $currentPassword = md5($_POST['current_password']);
    $newPassword = md5($_POST['new_password']);
    $confirmPassword = md5($_POST['confirm_password']);

    $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin['password'] === $currentPassword) {
        if ($newPassword === $confirmPassword) {
            $stmt = $pdo->prepare("UPDATE admin SET password = :password WHERE id = :id");
            $stmt->execute([':password' => $newPassword, ':id' => $_SESSION['admin_id']]);
            $message = "Password changed successfully!";
        } else {
            $message = "New password and confirm password do not match.";
        }
    } else {
        $message = "Current password is incorrect.";
    }
}

if ($isAdmin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_username'])) {
    $newUsername = $_POST['new_username'];

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username");
    $stmt->execute([':username' => $newUsername]);
    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingAdmin) {
        $message = "Username already exists. Please choose a different username.";
    } else {
        $stmt = $pdo->prepare("UPDATE admin SET username = :username WHERE id = :id");
        $stmt->execute([':username' => $newUsername, ':id' => $_SESSION['admin_id']]);
        $message = "Username changed successfully!";
    }
}

if ($isAdmin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_room'])) {
    $roomType = $_POST['room_type'];
    $maxRooms = $_POST['max_rooms'];
    $pricePerNight = $_POST['price_per_night'];

    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_type = :room_type");
    $stmt->execute([':room_type' => $roomType]);
    $existingRoom = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingRoom) {
        $message = "Room type already exists.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO rooms (room_type, max_rooms, price_per_night, available_rooms)
            VALUES (:room_type, :max_rooms, :price_per_night, :max_rooms)
        ");
        $stmt->execute([
            ':room_type' => $roomType,
            ':max_rooms' => $maxRooms,
            ':price_per_night' => $pricePerNight
           
        ]);
        $message = "Room type added successfully!";
    }
}

if ($isAdmin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_room_price'])) {
    $roomType = $_POST['room_type'];
    $pricePerNight = $_POST['price_per_night'];

    $stmt = $pdo->prepare("
        UPDATE rooms 
        SET price_per_night = :price 
        WHERE room_type = :room_type
    ");
    $stmt->execute([
        ':price' => $pricePerNight,
        ':room_type' => $roomType
    ]);
    $message = "Room price updated successfully!";
}

if ($isAdmin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_payment_method'])) {
    $methodName = $_POST['method_name'];

    $stmt = $pdo->prepare("INSERT INTO payment_methods (method_name) VALUES (:method)");
    $stmt->execute([':method' => $methodName]);
    $message = "Payment method added successfully!";
}

if ($isAdmin && isset($_GET['delete_payment_method'])) {
    $methodId = $_GET['delete_payment_method'];

    $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE method_id = :method_id");
    $stmt->execute([':method_id' => $methodId]);
    $message = "Payment method deleted successfully!";
}

if ($isAdmin && isset($_GET['delete_booking'])) {
    $bookingId = $_GET['delete_booking'];
    
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id = :id AND payment_status = 'pending'");
    $stmt->execute([':id' => $bookingId]);
    
    if ($stmt->rowCount() > 0) {
        $message = "Booking deleted successfully!";
    } else {
        $message = "Could not delete booking - payment already confirmed or booking doesn't exist.";
    }
}
if ($isAdmin && isset($_GET['delete_room'])) {
    $bookingId = $_GET['delete_room'];
    
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = :id ");
    $stmt->execute([':id' => $bookingId]);
    
    if ($stmt->rowCount() > 0) {
        $message = "Room deleted successfully!";
    } 
}

$stmt = $pdo->prepare("SELECT * FROM rooms");
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM bookings");
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM payments");
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM payment_methods");
$stmt->execute();
$paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM user_comments");
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
    
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

      
        header {
            background-color: #222;
            color: white;
            text-align: center;
            padding: 20px 0;
        }

        header h1 {
            font-size: 2em;
            margin: 0;
        }

       
        .admin-panel {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        h2 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 20px;
        }
        a {
            text-decoration: none ;
            color: white;
        }

        form {
            background-color: #fafafa;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        label {
            font-size: 1em;
            margin-bottom: 5px;
            display: inline-block;
        }

        input[type="text"], input[type="number"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button[type="submit"], button[type="button"] {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 1.1em;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover, button[type="button"]:hover {
            background-color: #218838;
        }

       
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
            text-align: left;
            padding: 12px;
        }

        th {
            background-color: #f8f9fa;
            color: #555;
        }

        td {
            background-color: white;
        }

       
        #message {
            color: green;
            font-size: 1.1em;
            margin-top: 15px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            h1 {
                font-size: 1.8em;
            }

            .admin-panel {
                padding: 10px;
            }

            form {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<body>
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div class="sub-header">
            <?php if ($isAdmin): ?>
                <button type="button"><a href="admin.php?logout=true">Logout</a></button>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <?php if (!$isAdmin): ?>
           
            <div class="login-container" id="login-form">
                <h2>Admin Login</h2>
                <form method="POST">
                    <label for="username">Username:</label>
                    <input type="text" name="username" required>
                    <label for="password">Password:</label>
                    <input type="password" name="password" required>
                    <button type="submit" name="login">Login</button>
                </form>
                <p class="error"><?= $message ?></p>
            </div>
        <?php else: ?>
            
            <div class="admin-panel">
                <h1>Admin Dashboard</h1>

                <form method="POST">
                    <h2>Change Username</h2>
                    <label for="new_username">New Username:</label>
                    <input type="text" name="new_username" required>
                    <button type="submit" name="change_username">Change Username</button>
                </form>

                <form method="POST">
                    <h2>Change Password</h2>
                    <label for="current_password">Current Password:</label>
                    <input type="password" name="current_password" required>
                    <label for="new_password">New Password:</label>
                    <input type="password" name="new_password" required>
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" name="confirm_password" required>
                    <button type="submit" name="change_password">Change Password</button>
                </form>

                <form method="POST">
                    <h2>Add New Room Type</h2>
                    <label for="room_type">Room Type:</label>
                    <input type="text" name="room_type" required>
                    <label for="max_rooms">Number of Rooms:</label>
                    <input type="number" name="max_rooms" required>
                    <label for="price_per_night">Price Per Night:</label>
                    <input type="number" name="price_per_night" step="0.01" required>
                    <button type="submit" name="add_room">Add Room Type</button>
                </form>

                <form method="POST">
                    <h2>Update Room Price</h2>
                    <label for="room_type">Room Type:</label>
                    <select name="room_type" required>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= htmlspecialchars($room['room_type']) ?>">
                                <?= htmlspecialchars($room['room_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="price_per_night">New Price Per Night:</label>
                    <input type="number" name="price_per_night" step="0.01" required>
                    <button type="submit" name="update_room_price">Update Room Price</button>
                </form>

                <form method="POST">
                    <h2>Add Payment Method</h2>
                    <label for="method_name">Payment Method Name:</label>
                    <input type="text" name="method_name" required>
                    <button type="submit"  name="add_payment_method">Add Payment Method</button>
                </form>

                <h2>Payment Methods</h2>
                <table>
                    <tr>
                        <th>Method Name</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($paymentMethods as $method): ?>
                        <tr>
                            <td><?= htmlspecialchars($method['method_name']) ?></td>
                            <td>
                                <button type="button">
                                    <a href="admin.php?delete_payment_method=<?= $method['method_id'] ?>">Remove</a>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <form method="GET" action="admin.php">
                    <h2>Filter and Sort Bookings</h2>
                    <label for="room_type">Room Type:</label>
                    <select name="room_type">
                        <option value="">All</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= htmlspecialchars($room['room_type']) ?>" <?= isset($_GET['room_type']) && $_GET['room_type'] === $room['room_type'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($room['room_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sort_date">Sort by Date:</label>
                    <select name="sort_date">
                        <option value="asc" <?= isset($_GET['sort_date']) && $_GET['sort_date'] === 'asc' ? 'selected' : '' ?>>Ascending</option>
                        <option value="desc" <?= isset($_GET['sort_date']) && $_GET['sort_date'] === 'desc' ? 'selected' : '' ?>>Descending</option>
                    </select>
                    <button type="submit">Apply Filter</button>
                </form>

               
                <h2>Rooms</h2>
                <table>
                    <tr>
                        <th>Room Type</th>
                        <th>Max Rooms</th>
                        
                        <th>Available Rooms</th>
                        <th>Price Per Night</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?= htmlspecialchars($room['room_type']) ?></td>
                            <td><?= htmlspecialchars($room['max_rooms']) ?></td>
                            
                            <td><?= htmlspecialchars($room['available_rooms']) ?></td> 
                            <td>$<?= htmlspecialchars($room['price_per_night']) ?></td>
                            <td>
                                <button type="button">
                                    <a href="admin.php?delete_room=<?= $room['id'] ?>">Delete</a>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                
                <h2>Bookings</h2>
                <table>
                    <tr>
                        <th>Booking ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Room Details</th>
                        <th>Total Payment</th>
                        <th>Payment Status</th>
                        <th>Payment Method</th> 
                        <th>Action</th>
                    </tr>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['booking_id']) ?></td>
                            <td><?= htmlspecialchars($booking['name']) ?></td>
                            <td><?= htmlspecialchars($booking['email']) ?></td>
                            <td><?= htmlspecialchars($booking['phone']) ?></td>
                            <td>
                                <?php
                                $roomDetails = json_decode($booking['room_details'], true);
                                foreach ($roomDetails as $room): ?>
                                    <p><strong>Room Type:</strong> <?= htmlspecialchars($room['roomType']) ?></p>
                                    <p><strong>Number of Rooms:</strong> <?= htmlspecialchars($room['numRooms']) ?></p>
                                    <p><strong>Check-in:</strong> <?= htmlspecialchars($room['checkIn']) ?></p>
                                    <p><strong>Check-out:</strong> <?= htmlspecialchars($room['checkOut']) ?></p>
                                <?php endforeach; ?>
                            </td>
                            <td>$<?= htmlspecialchars($booking['total_payment']) ?></td>
                            <td><?= htmlspecialchars($booking['payment_status']) ?></td>
                            <td><?= htmlspecialchars($booking['payment_method']) ?></td> 
                            <td>
                                <?php if ($booking['payment_status'] === 'pending'): ?>
                                    <button type="button">
                                        <a href="admin.php?delete_booking=<?= $booking['booking_id'] ?>"> Delete </a>
                                    </button>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <h2>User Comments</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Comment</th>
                        <th>Additional Message</th>
                        <th>Created At</th>
                    </tr>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td><?= htmlspecialchars($comment['id']) ?></td>
                            <td><?= htmlspecialchars($comment['full_name']) ?></td>
                            <td><?= htmlspecialchars($comment['email']) ?></td>
                            <td><?= htmlspecialchars($comment['phone']) ?></td>
                            <td><?= htmlspecialchars($comment['comment']) ?></td>
                            <td><?= htmlspecialchars($comment['additional_message']) ?></td>
                            <td><?= htmlspecialchars($comment['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                
                <p id="message"><?= $message ?></p>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>2025 &copy; Luxury Hotel. All Rights Reserved.</p>
    </footer>
</body>
</body>
</html>