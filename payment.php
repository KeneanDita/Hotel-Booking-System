<?php
session_start();

// Database connection
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

// Retrieve booking ID from URL
if (!isset($_GET['booking_id'])) {
    die("Invalid booking ID.");
}
$bookingId = $_GET['booking_id'];

// Fetch booking details
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

// Fetch payment methods from database
try {
    $stmt = $pdo->query("SELECT * FROM payment_methods");
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching payment methods: " . $e->getMessage());
}

// Calculate total payment based on room type, number of rooms, and number of days
$rooms = json_decode($booking['room_details'], true);
$totalPayment = 0;

foreach ($rooms as $room) {
    // Fetch price per night for the room type
    $stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE room_type = :room_type");
    $stmt->execute([':room_type' => $room['roomType']]);
    $roomPrice = $stmt->fetchColumn();

    if (!$roomPrice) {
        die("Price not found for room type: " . $room['roomType']);
    }

    // Calculate number of days
    $checkIn = new DateTime($room['checkIn']);
    $checkOut = new DateTime($room['checkOut']);
    $interval = $checkIn->diff($checkOut);
    $numberOfDays = $interval->days;

    // Calculate total payment for this room
    $totalPayment += ($roomPrice * $room['numRooms'] * $numberOfDays);
}

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmPayment'])) {
    $paymentMethod = $_POST['payment_method']; // Get selected payment method

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Update booking status to 'completed' and store payment method
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

        // Commit transaction
        $pdo->commit();

        // Redirect to success page
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f5f5;
            color: #333;
            text-align: center;
            padding: 20px;
        }

        .header {
            background: linear-gradient(to right, #ff4b1f, #ff9068);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin: 10px 0;
            font-weight: bold;
        }

        .header h2 {
            font-size: 1.5rem;
            margin: 10px 0;
            font-weight: normal;
        }

        .back-button button {
            background: white;
            color: #ff4b1f;
            border: none;
            padding: 10px 15px;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }

        .back-button button:hover {
            background: #ff4b1f;
            color: white;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 20px;
        }

        .sub-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        label {
            font-weight: bold;
            text-align: left;
        }

        input, select, button {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            width: 100%;
        }

        button {
            background: #ff4b1f;
            color: white;
            cursor: pointer;
            border: none;
        }

        button:hover {
            background: #e63900;
        }

        .error {
            color: red;
            font-size: 0.9rem;
        }

        footer {
            background: #222;
            color: white;
            padding: 15px;
            margin-top: 20px;
            width: 100%;
            border-radius: 10px;
        }

        .room-detail {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background: #f9f9f9;
        }

        .booking-id {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ff4b1f;
            margin-bottom: 20px;
        }

        @media screen and (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .header h2 {
                font-size: 1.2rem;
            }

            .container {
                width: 100%;
                padding: 10px;
            }
        }

        @media screen and (max-width: 480px) {
            .header {
                padding: 15px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .header h2 {
                font-size: 1rem;
            }

            .back-button button {
                padding: 8px 12px;
                font-size: 0.9rem;
            }

            .sub-container {
                padding: 15px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <h1>Luxury Hotel</h1>
        <h2>Complete Your Payment</h2>
        <div class="back-button">
            <button type="button"><a href="room-booking.php" style="text-decoration: none; color: inherit;">Back to Booking</a></button>
        </div>
    </div>

    <!-- Payment Details Section -->
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

            <!-- Payment Confirmation Form -->
            <form method="post">
                <label for="payment_method">Payment Method:</label>
                <select id="payment_method" name="payment_method" required>
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= htmlspecialchars($method['method_name']) ?>">
                            <?= htmlspecialchars($method['method_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="confirmPayment">Confirm Payment</button>
            </form>
            <button onclick="window.location.href='room-booking.php';">Cancel</button>
        </div>
    </div>

    <!-- Footer Section -->
    <footer>
        <p>2025 &copy; Copy Right Reserved, Luxury Hotel</p>
    </footer>
</body>
</html>