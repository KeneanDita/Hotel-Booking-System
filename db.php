<?php

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "hotel_booking";

$adminpassword = password_hash('12345678', PASSWORD_DEFAULT);
$adminusername = "admin";


$conn = new mysqli($servername, $username, $password);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database:  $conn->error <br>";
}


$conn->select_db($dbname);


$sql = "CREATE TABLE IF NOT EXISTS admin (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    UNIQUE KEY username (username)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'admin' created successfully<br>";
} else {
    echo "Error creating table 'admin':  $conn->error <br>";
}


$stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $adminusername, $adminpassword);
if ($stmt->execute()) {
    echo "Admin data inserted successfully<br>";
} else {
    echo "Error inserting admin data: $stmt->error <br>";
}
$stmt->close();


$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    room_details LONGTEXT NOT NULL,
    total_payment DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'confirmed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(40) DEFAULT 'none',
    UNIQUE KEY booking_id (booking_id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'bookings' created successfully<br>";
} else {
    echo "Error creating table 'bookings':  $conn->error <br>";
}


$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(20) NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'payments' created successfully<br>";
} else {
    echo "Error creating table 'payments':  $conn->error <br>";
}


$sql = "CREATE TABLE IF NOT EXISTS payment_methods (
    method_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(50) NOT NULL,
    UNIQUE KEY method_name (method_name)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'payment_methods' created successfully<br>";
} else {
    echo "Error creating table 'payment_methods': $conn->error <br>";
}


$sql = "CREATE TABLE IF NOT EXISTS rooms (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    room_type VARCHAR(50) NOT NULL,
    max_rooms INT(11) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    reserved_rooms INT(11) DEFAULT 0,
    available_rooms INT(11) DEFAULT 0,
    UNIQUE KEY room_type (room_type)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'rooms' created successfully<br>";
} else {
    echo "Error creating table 'rooms':  $conn->error <br>";
}


$sql = "CREATE TABLE IF NOT EXISTS user_comments (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    comment TEXT,
    additional_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'user_comments' created successfully<br>";
} else {
    echo "Error creating table 'user_comments':  $conn->error <br>";
}


$conn->close();
?>