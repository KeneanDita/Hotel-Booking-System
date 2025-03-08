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

// Fetch room types from the database
$roomTypes = [];
try {
    $stmt = $pdo->query("SELECT room_type FROM rooms");
    $roomTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error fetching room types: " . $e->getMessage());
}

$nameError = $emailError = $phoneError = $message = "";
$name = $email = $phone = "";
$roomTypesInput = $numRooms = $checkIns = $checkOuts = []; 

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $phone = htmlspecialchars($_POST['phone'] ?? '');
    $roomTypesInput = $_POST['roomType'] ?? [];
    $numRooms = $_POST['numRooms'] ?? [];
    $checkIns = $_POST['checkIn'] ?? [];
    $checkOuts = $_POST['checkOut'] ?? [];

    // Validate inputs
    if (empty($name)) $nameError = "Name is required";
    if (empty($phone)) $phoneError = "Phone number is required";
    elseif (!preg_match('/^[0-9]{10}$/', $phone)) $phoneError = "Invalid phone number format. It should be 10 digits.";
    if (empty($email)) $emailError = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $emailError = "Please enter a valid email format";

    // Check room availability
    $isAvailable = true;
    $availabilityErrors = [];
    foreach ($roomTypesInput as $index => $roomType) {
        $requiredRooms = (int)$numRooms[$index];

        // Query the database to check available rooms
        $stmt = $pdo->prepare("SELECT available_rooms FROM rooms WHERE room_type = :room_type");
        $stmt->execute([':room_type' => $roomType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['available_rooms'] >= $requiredRooms) {
            // Room is available
        } else {
            $isAvailable = false;
            $availabilityErrors[] = "Not enough $roomType rooms available. Only " . ($row['available_rooms'] ?? 0) . " left.";
        }
    }

    if (!$isAvailable) {
        $message = implode("<br>", $availabilityErrors);
    } else {
        // Prepare room details
        if (empty($nameError) && empty($emailError) && empty($phoneError) && empty($message)) {$rooms = [];
        foreach ($roomTypesInput as $index => $roomType) {
            $rooms[] = [
                'roomType' => $roomType,
                'numRooms' => $numRooms[$index],
                'checkIn' => $checkIns[$index],
                'checkOut' => $checkOuts[$index]
            ];
        }

        // Generate a unique booking ID
        $bookingId = 'BOOK' . uniqid();

        // Insert booking into the database
        try {
            $pdo->beginTransaction();

            // Insert booking
            $stmt = $pdo->prepare("
                INSERT INTO bookings (booking_id, name, email, phone, room_details)
                VALUES (:booking_id, :name, :email, :phone, :room_details)
            ");
            $stmt->execute([
                ':booking_id' => $bookingId,
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':room_details' => json_encode($rooms),
            ]);

            // Update room availability
            foreach ($roomTypesInput as $index => $roomType) {
                $requiredRooms = (int)$numRooms[$index];
                $stmt = $pdo->prepare("
                    UPDATE rooms
                    SET available_rooms = available_rooms - :required_rooms
                    WHERE room_type = :room_type
                ");
                $stmt->execute([
                    ':required_rooms' => $requiredRooms,
                    ':room_type' => $roomType
                ]);
            }

            $pdo->commit();

            // Redirect to payment page
            header("Location: payment.php?booking_id=$bookingId");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Error saving booking: " . $e->getMessage());
        }
    }}
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Booking</title>
    <link rel="stylesheet" href="style sheet/booking.css">
    <style>
        
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    
   
 }.container {
    width: 400px;
    margin: 50px auto;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}
.error {
    color: red;
    font-size: 0.9em;
}
.availability-message {
    color: green;
    font-size: 0.9em;
}
.room {
    border: 1px solid #ccc;
    padding: 10px;
    margin-bottom: 10px;
}
.removeRoom {
    background-color: #ff4d4d;
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
}
.removeRoom:hover {
    background-color: #cc0000;
} 
         
.sub-container{
    width: 100%;
}

h1 {
    text-align: center;
    color: #ffffff;
}

p {
    text-align: center;
    color: #ffffff;
}
.back-button {
    
    flex-direction: column;
    gap: 5px;
    position: absolute;
    top: 20px;
    right: 20px;
    cursor: pointer;
}


/* Header styling */
.header {
    background-color: #8b5cf6;
    color: rgb(233, 233, 233);
    text-align: center;
    padding: 5px;
    font-size: 12px;
}

input, select, button ,textarea{
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 16px;
}

button {
    background: #4e4434;
    color: white;
    font-size: 18px;
    cursor: pointer;
    border: none;
    transition: 0.3s;
}

button:hover {
    background: #d35400;
}

    </style>
</head>
<body>
    <div class="header">
        <h2>🔥 Book Now, Enjoy Exclusive Savings</h2>
        <h1>Luxury Hotel</h1>
        <div class="back-button">
            <button type="button"><a href="index.php">Back to Home</a></button>
            <button type="button"><a href="updatebooking.php">Update Booking</a></button>
        </div>
    </div>

    <div class="container">
        <div class="sub-container">
            <h2>Book a Hotel Room</h2>
            <form method="post" action="">
                <label for="name">Name:</label>
                <span class="error">*<?php echo $nameError ?></span>
                <input type="text" id="name" name="name" placeholder="Your name...">

                <label for="email">Email:</label>
                <span class="error">*<?php echo $emailError ?></span>
                <input type="email" id="email" value="<?php echo htmlspecialchars($name); ?>"name="email" placeholder="eg. mesfin@ayele.com">

                <label for="phone">Phone:</label>
                <span class="error">*<?php echo $phoneError ?></span>
                <input type="tel" id="phone" name="phone" placeholder="eg. 0911132343">

                <!-- Room Selection -->
                <div id="roomSelection">
                    <div class="room">
                    <label for="roomType1">Room Type:</label>
                        <select id="roomType1" name="roomType[]">
                            <?php foreach ($roomTypes as $roomType): ?>
                                <option value="<?php echo htmlspecialchars($roomType); ?>">
                                    <?php echo htmlspecialchars($roomType); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="numRooms1">Number of Rooms:</label>
                        <input type="number" id="numRooms1" name="numRooms[]" min="1" value="1">

                        <label for="check-in1">Check-in:</label>
                        <input type="date" id="check-in1" name="checkIn[]">

                        <label for="check-out1">Check-out:</label>
                        <input type="date" id="check-out1" name="checkOut[]">

                        <button type="button" class="removeRoom">Remove</button>
                        <p class="availability-message"></p>
                    </div>
                </div>

                <!-- Add More Rooms Button -->
                <button type="button" id="addRoom">Add More Rooms</button>

                <p id="availability"><span class="error">*<?php echo $message ?></span></p>

                <button type="submit">Book Now</button>
            </form>
        </div>
    </div>

    <footer>
        <p>2025 &copy; Copy Right Reserved, Luxury Hotel</p>
    </footer>

    <script>
       document.getElementById("addRoom").addEventListener("click", function () {
    const roomSelection = document.getElementById("roomSelection");
    const roomCount = roomSelection.children.length + 1;

    const newRoom = document.createElement("div");
    newRoom.classList.add("room");
    newRoom.innerHTML = `
        <label for="roomType${roomCount}">Room Type:</label>
        <select id="roomType${roomCount}" name="roomType[]">
            <?php foreach ($roomTypes as $roomType): ?>
                <option value="<?php echo htmlspecialchars($roomType); ?>">
                    <?php echo htmlspecialchars($roomType); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="numRooms${roomCount}">Number of Rooms:</label>
        <input type="number" id="numRooms${roomCount}" name="numRooms[]" min="1" value="1">

        <label for="check-in${roomCount}">Check-in:</label>
        <input type="date" id="check-in${roomCount}" name="checkIn[]">

        <label for="check-out${roomCount}">Check-out:</label>
        <input type="date" id="check-out${roomCount}" name="checkOut[]">

        <button type="button" class="removeRoom">Remove</button>
        <p class="availability-message"></p>
    `;

    roomSelection.appendChild(newRoom);
});

        // JavaScript to remove rooms
        document.addEventListener("click", function (event) {
            if (event.target.classList.contains("removeRoom")) {
                const room = event.target.closest(".room");
                room.remove();
            }
        });

        
        document.querySelectorAll('select[name="roomType[]"], input[name="numRooms[]"]').forEach(element => {
            element.addEventListener('change', function () {
                const roomType = this.closest('.room').querySelector('select[name="roomType[]"]').value;
                const numRooms = this.closest('.room').querySelector('input[name="numRooms[]"]').value;

                fetch(`check_availability.php?roomType=${roomType}`)
                    .then(response => response.json())
                    .then(data => {
                        const availabilityMessage = data.available_rooms >= numRooms
                            ? `Available: ${data.available_rooms} rooms`
                            : `Only ${data.available_rooms} rooms available`;
                        this.closest('.room').querySelector('.availability-message').innerText = availabilityMessage;
                    });
            });
        });
    </script>
</body>
</html>
