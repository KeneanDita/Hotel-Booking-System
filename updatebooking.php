<?php
session_start();


$host = 'localhost';
$dbname = 'hotel_booking';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$currentBooking = "BOOK67d4925d1ed14";
$updateError = null;
$successMessage = null;


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submitBookingId'])) {
    if (isset($_POST['bookingId'])) {
        $bookingId = trim($_POST['bookingId']); 

        
        if (empty($bookingId)) {
            $updateError = "Booking ID cannot be empty!";
        } else {
            try {
                
                
                $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("s", $bookingId);
                $stmt->execute();
                $result = $stmt->get_result();
                $currentBooking = $result->fetch_assoc();

                if (!$currentBooking) {
                    $updateError = "Booking ID '$bookingId' not found!";
                }
            } catch (Exception $e) {
                $updateError = $e->getMessage();
            } finally {
                if (isset($stmt)) {
                    $stmt->close();
                }
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateBooking'])) {
    if (isset($_POST['bookingId'])) {
        $bookingId = trim($_POST['bookingId']);

        try {
            

            $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("s", $bookingId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentBooking = $result->fetch_assoc();

            if (!$currentBooking) {
                $updateError = "Booking ID '$bookingId' not found!";
            } else {
               
                $name = htmlspecialchars($_POST['updateName'] ?? '');
                $email = htmlspecialchars($_POST['updateEmail'] ?? '');
                $phone = htmlspecialchars($_POST['updatePhone'] ?? '');
                $roomTypesInput = $_POST['updateRoomType'] ?? [];
                $numRooms = $_POST['updateNumRooms'] ?? [];
                $checkIns = $_POST['updateCheckIn'] ?? [];
                $checkOuts = $_POST['updateCheckOut'] ?? [];

                
                
                $updatedRooms = [];
                foreach ($roomTypesInput as $index => $roomType) {
                    $updatedRooms[] = [
                        'roomType' => $roomType,
                        'numRooms' => $numRooms[$index],
                        'checkIn' => $checkIns[$index],
                        'checkOut' => $checkOuts[$index]
                    ];
                }

                
                $stmt = $conn->prepare("
                    UPDATE bookings
                    SET name = ?, email = ?, phone = ?, room_details = ?
                    WHERE booking_id = ?
                ");
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $roomDetailsJson = json_encode($updatedRooms);
                $stmt->bind_param("sssss", $name, $email, $phone, $roomDetailsJson, $bookingId);
                if ($stmt->execute()) {
                    $successMessage = "Booking updated successfully!";
                } else {
                    throw new Exception("Error updating booking: " . $stmt->error);
                }
            }
        } catch (Exception $e) {
            $updateError = $e->getMessage();
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancelBooking'])) {
    if (isset($_POST['cancelBookingId'])) {
        $bookingId = trim($_POST['cancelBookingId']); 

        try {
            
            $conn->begin_transaction();

            
            $stmt = $conn->prepare("DELETE FROM payments WHERE booking_id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("s", $bookingId);
            if (!$stmt->execute()) {
                throw new Exception("Error deleting from payments: " . $stmt->error);
            }

            
            $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("s", $bookingId);
            if (!$stmt->execute()) {
                throw new Exception("Error deleting from bookings: " . $stmt->error);
            }

           
            $conn->commit();
            $successMessage = "Booking canceled successfully!";
        } catch (Exception $e) {
           
            $conn->rollback();
            $updateError = $e->getMessage();
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Booking</title>
    <link rel="stylesheet" href="style sheet/update.css">
</head>
<body>
    <div class="header">
        <h2>🔥 Book Now, Enjoy Exclusive Savings</h2>
        <h1>Luxury Hotel</h1>
        <div class="back-button">
            <button onclick="location.href='index.php'">Back to Home</button>
        </div>
    </div>

    <div class="container">
        <div class="toggle-buttons">
            <button id="showUpdate">Update Booking</button>
            <button id="showCancel">Cancel Booking</button>
        </div>

        <div id="bookingIdForm" class="form-section update-form">
            <form method="POST" action="">
                <h2>Enter Booking ID</h2>
                <label for="bookingId">Booking ID:</label>
                <input type="text" id="bookingId" name="bookingId" required>
                <input type="hidden" name="submitBookingId" value="1">
                <button type="submit">Submit</button>
            </form>
        </div>

        <div id="bookedDetails" class="form-section" style="display: <?= $currentBooking ? 'block' : 'none'; ?>;">
            <?php if ($currentBooking): ?>
                <h2>Current Booking Details</h2>
                <p><strong>Booking ID:</strong> <?= htmlspecialchars($currentBooking['booking_id']) ?></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($currentBooking['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($currentBooking['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($currentBooking['phone']) ?></p>
                <h3>Rooms Booked:</h3>
                <?php
                $rooms = json_decode($currentBooking['room_details'], true);
                foreach ($rooms as $index => $room): ?>
                    <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                        <p><strong>Room <?= $index + 1 ?>:</strong></p>
                        <p><strong>Room Type:</strong> <?= htmlspecialchars($room['roomType']) ?></p>
                        <p><strong>Number of Rooms:</strong> <?= htmlspecialchars($room['numRooms']) ?></p>
                        <p><strong>Check-in Date:</strong> <?= htmlspecialchars($room['checkIn']) ?></p>
                        <p><strong>Check-out Date:</strong> <?= htmlspecialchars($room['checkOut']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?= $updateError ?? 'Enter a valid Booking ID to view details.' ?></p>
            <?php endif; ?>
        </div>

        <div id="updateForm" class="form-section update" style="display: <?= $currentBooking ? 'block' : 'none'; ?>;">
            <form method="POST">
                <h2>Manage Your Booking</h2>

                <?php if ($currentBooking): ?>
                    <label for="bookingId">Booking ID:</label>
                    <input type="text" id="bookingId" name="bookingId" value="<?= htmlspecialchars($currentBooking['booking_id']) ?>" readonly>

                    <label for="updateName">Name:</label>
                    <input type="text" id="updateName" name="updateName" value="<?= htmlspecialchars($currentBooking['name']) ?>">

                    <label for="updateEmail">Email:</label>
                    <input type="email" id="updateEmail" name="updateEmail" value="<?= htmlspecialchars($currentBooking['email']) ?>">

                    <label for="updatePhone">Phone:</label>
                    <input type="tel" id="updatePhone" name="updatePhone" value="<?= htmlspecialchars($currentBooking['phone']) ?>">

                    <h3>Update Rooms:</h3>
                    <?php
                    $rooms = json_decode($currentBooking['room_details'], true);
                    foreach ($rooms as $index => $room): ?>
                        <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                            <p><strong>Room <?= $index + 1 ?>:</strong></p>
                            <label for="updateRoomType<?= $index ?>">Room Type:</label>
                            <select id="updateRoomType<?= $index ?>" name="updateRoomType[<?= $index ?>]">
                                <?php foreach ($roomTypes as $roomType): ?>
                                    <option value="<?= htmlspecialchars($roomType); ?>" <?= $room['roomType'] == $roomType ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($roomType); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label for="updateNumRooms<?= $index ?>">Number of Rooms:</label>
                            <input type="number" id="updateNumRooms<?= $index ?>" name="updateNumRooms[<?= $index ?>]" value="<?= htmlspecialchars($room['numRooms']) ?>">

                            <label for="updateCheckIn<?= $index ?>">Check-in Date:</label>
                            <input type="date" id="updateCheckIn<?= $index ?>" name="updateCheckIn[<?= $index ?>]" value="<?= htmlspecialchars($room['checkIn']) ?>">

                            <label for="updateCheckOut<?= $index ?>">Check-out Date:</label>
                            <input type="date" id="updateCheckOut<?= $index ?>" name="updateCheckOut[<?= $index ?>]" value="<?= htmlspecialchars($room['checkOut']) ?>">
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" name="updateBooking">Update Booking</button>
                <?php else: ?>
                    <p><?= $updateError ?? 'Please enter a valid booking ID to edit the details.' ?></p>
                <?php endif; ?>
            </form>
        </div>

        <div id="cancelForm" class="form-section">
            <form method="POST">
                <h2>Cancel Your Booking</h2>

                <label for="cancelBookingId">Booking ID:</label>
                <input type="text" id="cancelBookingId" name="cancelBookingId" required>

                <button type="submit" name="cancelBooking">Cancel Booking</button>
            </form>
            
            <?php if ($successMessage): ?>
                <p><?= $successMessage ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <footer>
            <p>2025 &copy; Copy Right Reserved, Luxury Hotel</p>
        </footer>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const showUpdateButton = document.getElementById("showUpdate");
            const showCancelButton = document.getElementById("showCancel");
            const bookingIdForm = document.getElementById("bookingIdForm");
            const bookedDetails = document.getElementById("bookedDetails");
            const updateForm = document.getElementById("updateForm");
            const cancelForm = document.getElementById("cancelForm");
            const submitButton = document.getElementById("submitButton");
            const bookingForm = document.querySelector("#bookingIdForm form");

            showUpdateButton.addEventListener("click", function () {
                bookingIdForm.style.display = "block";
                bookedDetails.style.display = "none";
                updateForm.style.display = "none";
                cancelForm.style.display = "none";
            });

            bookingForm.addEventListener("submit", function (e) {
                e.preventDefault(); 
                bookingIdForm.style.display = "none";
                bookedDetails.style.display = "block";
                updateForm.style.display = "block";
            });

            showCancelButton.addEventListener("click", function () {
                bookingIdForm.style.display = "none";
                bookedDetails.style.display = "none";
                updateForm.style.display = "none";
                cancelForm.style.display = "block";
            });
        });
    </script>
</body>
</html>