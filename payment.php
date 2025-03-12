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


if (!isset($_GET['booking_id'])) {
    die("Invalid booking ID.");
}
$bookingId = $_GET['booking_id'];


try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = :booking_id");
    $stmt->execute([':booking_id' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        die("Booking not found.");
    }
} catch (PDOException $e) {
    die("Error fetching booking details: " . $e->getMessage());
}


try {
    $stmt = $pdo->query("SELECT * FROM payment_methods");
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching payment methods: " . $e->getMessage());
}


$rooms = json_decode($booking['room_details'], true);
$totalPayment = 0;

foreach ($rooms as $room) {
    
    $stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE room_type = :room_type");
    $stmt->execute([':room_type' => $room['roomType']]);
    $roomPrice = $stmt->fetchColumn();

    if (!$roomPrice) {
        die("Price not found for room type: " . $room['roomType']);
    }

    
    $checkIn = new DateTime($room['checkIn']);
    $checkOut = new DateTime($room['checkOut']);
    $interval = $checkIn->diff($checkOut);
    $numberOfDays = $interval->days;

   
    $totalPayment += ($roomPrice * $room['numRooms'] * $numberOfDays);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmPayment'])) {
    $paymentMethod = $_POST['payment_method']; 

    try {
       
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET payment_status = 'confirmed', 
                total_payment = :total_payment, 
                payment_method = :payment_method 
                
            WHERE booking_id = :booking_id
        ");
        $stmt->execute([
            ':total_payment' => $totalPayment,
            ':payment_method' => $paymentMethod,
            ':booking_id' => $bookingId
        ]);


        $pdo->commit();

        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error processing payment: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <link rel="stylesheet" href="style sheet/payment.css">
    
</head>
<body>

    <div class="header">
        <h1>Luxury Hotel</h1>
        <h2>Complete Your Payment</h2>
        <div class="back-button">
            <button type="button"><a href="room-booking.php" style="text-decoration: none; color: inherit;">Back to Booking</a></button>
        </div>
    </div>

    <div class="container">
        <div class="sub-container">
            <h1>Payment Details</h1>
            <div class="booking-id">
                <strong>Booking ID:</strong> <?= htmlspecialchars($booking['booking_id']) ?>
            </div>
            <div id="paymentDetails">
                <p><strong>Name:</strong> <?= htmlspecialchars($booking['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($booking['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($booking['phone']) ?></p>
                <h3>Room Details:</h3>
                <?php foreach ($rooms as $index => $room): ?>
                    <div class="room-detail">
                        <p><strong>Room <?= $index + 1 ?>:</strong></p>
                        <p><strong>Room Type:</strong> <?= htmlspecialchars($room['roomType']) ?></p>
                        <p><strong>Number of Rooms:</strong> <?= htmlspecialchars($room['numRooms']) ?></p>
                        <p><strong>Check-in Date:</strong> <?= htmlspecialchars($room['checkIn']) ?></p>
                        <p><strong>Check-out Date:</strong> <?= htmlspecialchars($room['checkOut']) ?></p>
                    </div>
                <?php endforeach; ?>
                <p><strong>Total Payment:</strong> $<?= number_format($totalPayment, 2) ?></p>
            </div>

            <form method="post">
                <label for="payment_method">Payment Method:</label>
                <select id="payment_method" name="payment_method" required>
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= htmlspecialchars($method['method_name']) ?>">
                            <?= htmlspecialchars($method['method_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="btn">
                    <button type="submit" name="confirmPayment">Confirm Payment</button>
                </div>        
            </form>
            <div class="cancel-btn">
                <button onclick="window.location.href='room-booking.php';">Cancel</button>
            </div>
            
        </div>
    </div>

     <div class="footer">
        <footer>
            <p>2025 &copy; Copy Right Reserved, Luxury Hotel</p>
        </footer>
     </div>
    
</body>
</html>