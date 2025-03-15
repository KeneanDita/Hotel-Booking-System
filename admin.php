<?php
session_start();

$host = 'localhost';
$dbname = 'hotel_booking';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
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

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

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

    $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin['password'] === $currentPassword) {
        if ($newPassword === $confirmPassword) {
            $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $newPassword, $_SESSION['admin_id']);
            $stmt->execute();
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

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $newUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingAdmin = $result->fetch_assoc();

    if ($existingAdmin) {
        $message = "Username already exists. Please choose a different username.";
    } else {
        $stmt = $conn->prepare("UPDATE admin SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $newUsername, $_SESSION['admin_id']);
        $stmt->execute();
        $message = "Username changed successfully!";
    }
}

if ($isAdmin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_room'])) {
    $roomType = $_POST['room_type'];
    $maxRooms = $_POST['max_rooms'];
    $pricePerNight = $_POST['price_per_night'];

    $stmt = $conn->prepare("SELECT * FROM rooms WHERE room_type = ?");
    $stmt->bind_param("s", $roomType);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingRoom = $result->fetch_assoc();

    if ($existingRoom) {
        $message = "Room type already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO rooms (room_type, max_rooms, price_per_night, available_rooms) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sidi", $roomType, $maxRooms, $pricePerNight, $maxRooms);
        $stmt->execute();
        $message = "Room type added successfully!";
    }
}

if ($isAdmin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_room_price'])) {
    $roomType = $_POST['room_type'];
    $pricePerNight = $_POST['price_per_night'];

    $stmt = $conn->prepare("UPDATE rooms SET price_per_night = ? WHERE room_type = ?");
    $stmt->bind_param("ds", $pricePerNight, $roomType);
    $stmt->execute();
    $message = "Room price updated successfully!";
}

if ($isAdmin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_payment_method'])) {
    $methodName = $_POST['method_name'];

    $stmt = $conn->prepare("INSERT INTO payment_methods (method_name) VALUES (?)");
    $stmt->bind_param("s", $methodName);
    $stmt->execute();
    $message = "Payment method added successfully!";
}

if ($isAdmin && isset($_GET['delete_payment_method'])) {
    $methodId = $_GET['delete_payment_method'];

    $stmt = $conn->prepare("DELETE FROM payment_methods WHERE method_id = ?");
    $stmt->bind_param("i", $methodId);
    $stmt->execute();
    $message = "Payment method deleted successfully!";
}

if ($isAdmin && isset($_GET['delete_booking'])) {
    $bookingId = $_GET['delete_booking'];

    $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ? AND payment_status = 'pending'");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = "Booking deleted successfully!";
    } else {
        $message = "Could not delete booking - payment already confirmed or booking doesn't exist.";
    }
}

if ($isAdmin && isset($_GET['delete_room'])) {
    $roomId = $_GET['delete_room'];

    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = "Room deleted successfully!";
    }
}

$rooms = [];
$stmt = $conn->prepare("SELECT * FROM rooms");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

$bookings = [];
$stmt = $conn->prepare("SELECT * FROM bookings");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$payments = [];
$stmt = $conn->prepare("SELECT * FROM payments");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}

$paymentMethods = [];
$stmt = $conn->prepare("SELECT * FROM payment_methods");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $paymentMethods[] = $row;
}

$comments = [];
$stmt = $conn->prepare("SELECT * FROM user_comments");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style sheet/admin.css">
    
</head>
<body>
<body>
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div class="sub-header">
            <?php if ($isAdmin): ?>
                <button onclick="location.href='admin.php?logout=true'"><a href="index.php">Logout</a></button>
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
                    <div class="login-btn"><button type="submit" name="login">Login</button></div>
                    
                </form>
                <p class="error"><?= $message ?></p>
            </div>
        <?php else: ?>
            
            <div class="admin-panel">
            <div class="change">
                <div class="change-username"> 
                    <form method="POST">
                        <h2>Change Username</h2>
                        <label for="new_username">New Username:</label>
                        <input type="text" name="new_username" required>
                        <div class="change-btn">
                            <button type="submit" name="change_username">Change Username</button>
                        </div>       
                    </form>
                </div>
                <div class="change-password"> 
                    <form method="POST">
                        <h2>Change Password</h2>
                        <label for="current_password">Current Password:</label>
                        <input type="password" name="current_password" required>
                        <label for="new_password">New Password:</label>
                        <input type="password" name="new_password" required>
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" name="confirm_password" required>
                        <div class="change-btn">
                            <button type="submit" name="change_password">Change Password</button>
                        </div>
                        
                    </form>
                </div>
            </div>
            <div class="add">
                <div class="add-room">
                    <form method="POST">
                        <h2>Add New Room Type</h2>
                        <label for="room_type">Room Type:</label>
                        <input type="text" name="room_type" required>
                        <label for="max_rooms">Number of Rooms:</label>
                        <input type="number" name="max_rooms" required>
                        <label for="price_per_night">Price Per Night:</label>
                        <input type="number" name="price_per_night" step="0.01" required>
                        <div class="add-btn">
                            <button type="submit" name="add_room">Add Room Type</button>
                        </div>   
                    </form>
                </div>
                <div class="add-price">
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
                        <div class="add-btn">
                            <button type="submit" name="update_room_price">Update Room Price</button>
                        </div>       
                    </form> 
                </div>
                <div class="add-method">
                    <form method="POST">                       
                        <h2>Add Payment Method</h2>
                        <label for="method_name">Payment Method Name:</label>
                        <input type="text" name="method_name" required>
                        <div class="add-btn">
                            <button type="submit"  name="add_payment_method">Add Payment Method</button>
                        </div>                       
                   </form>
                </div>
            </div> 
            <div class="filter-method">
                <div class="payment-method">
                    <div class="method-title">
                        <h2>Payment Methods</h2>
                    </div>
                    <div class="method-table">
                        <table >
                            <tr>
                                <th>Method Name</th>
                                <th>Action</th>
                            </tr>
                            <?php foreach ($paymentMethods as $method): ?>
                                <tr>
                                    <td><?= htmlspecialchars($method['method_name']) ?></td>
                                    <td>
                                        <div class="btn">
                                            <button onclick="location.href='admin.php?delete_payment_method=<?= $method['method_id'] ?>'">Remove</button>
                                        </div>                                   
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>                          
                </div>         
                <div class="filter">
                    <form method="GET" action="admin.php">
                        <div class="filter-title">
                            <h2>Filter and Sort Bookings</h2>
                        </div>
                        <div class="filter-form">
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
                            <div class="filter-btn">
                                <button type="submit">Apply Filter</button>
                            </div>                        
                        </div>    
                    </form>
                </div>
            </div>
            <div class="room">
                <div class="room-title">
                    <h2>Rooms</h2>
                </div>
                <div class="room-table">
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
                                    <div class="btn">
                                         <button onclick="location.href='admin.php?delete_room=<?= $room['id'] ?>'">Delete</button>                               
                                    </div>
                                   
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>         
            </div>
             <div class="booking">
                <div class="booking-title">
                   <h2>Bookings</h2> 
                </div>
                <div class="booking-table">
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
                                        <div class="btn">
                                            <button onclick="location.href='admin.php?delete_booking=<?= $booking['booking_id'] ?>'">Delete </button>
                                        </div>    
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>             
             </div>         
              <div class="comment">
                <div class="comment-title">
                    <h2>User Comments</h2>
                </div>
                <div class="comment-table">
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
                </div>           
              </div>  
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