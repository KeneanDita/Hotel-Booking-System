
<?php
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['c_email'] ?? '');
    $phone = htmlspecialchars($_POST['c_phone'] ?? '');
    $comment = htmlspecialchars($_POST['comment'] ?? '');
    $additional_message = htmlspecialchars($_POST['additional'] ?? '');

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($phone)) {
        die("Name, email, and phone are required fields.");
    }

    // Insert data into the database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_comments (full_name, email, phone, comment, additional_message)
            VALUES (:full_name, :email, :phone, :comment, :additional_message)
        ");

        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email,
            ':phone' => $phone,
            ':comment' => $comment,
            ':additional_message' => $additional_message
        ]);

        echo "Thank you for your comment!";
    } catch (PDOException $e) {
        die("Error saving your comment: " . $e->getMessage());
    }
}
?>

<!-- Your existing HTML form for comments -->
 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style sheet/main.css ">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
    <title>Hotel Booking System</title>
</head>
<body>
    <div class="header">
        <h1>🔥 Book Now, Enjoy Exclusive Savings</h1>
        <div class="toogle" onclick="toggleMenu()">
            <div class="toogle-list" id="toogle1"></div>
            <div class="toogle-list" id="toogle2"></div>
            <div class="toogle-list" id="toogle3"></div>
        </div>
        <div class="headers">
            <h1>Luxury Hotel</h1>
            <div class="header-list">
                <a href="#intro"><h1>Home</h1></a>
                <a href="#about-us"><h1>About Us</h1></a>
                <a href="#list-room"><h1>Rooms</h1></a>
                <a href="#services"><h1>Services</h1></a>
                <a href="#contact-us"><h1>Contact Us</h1></a>
            </div>
            <div class="sub-header">
                <button onclick="location.href='room-booking.php'">Book Now</button> 
                <button onclick="location.href='admin.php'">Manage Rooms</button> <!-- Link to Admin Login -->
            </div>
        </div>
    </div>
     
        <div class="intro" id="intro">
            
    

<h1>Unparalleled Luxury & Comfort Awaits</h1>
<p> Experience the perfect blend of comfort, luxury, and style. Indulge in elegance, 
    unwind in sophistication, and enjoy world-class hospitality for an unforgettable stay. Your dream getaway starts here.</p>

    </div>

    <div class="about-us" id="about-us">
        <h1>About Us</h1>
        <div class="about-us-subdiv">
            <div class="about-us-image"><img src="images/pexels-aftabmirza-30761844 (1).jpg" alt="" class="img"></div>
            
          <div class="about-us-p"><p>Luxury Hotel is a wonderful place to stay, offering comfortable rooms,
             friendly service, and many great facilities. Each room is clean, spacious, and designed to help you relax.
              Guests can enjoy delicious food at our restaurant, take a swim in the pool, or unwind at the spa.
               Our staff is always ready to assist and make your stay easy and enjoyable. Booking a room is simple,
                and we ensure a peaceful and comfortable experience for every guest. Whether you are traveling for work or vacation,
                 Luxury Hotel is the perfect choice for a relaxing stay.</p>
               </div>  </div>
    </div>


    <div class="list-room" id="list-room">
        <h1>Here is the Rooms we provides for Our Guests</h1>
        <p>At Luxury Hotel, we offer different types of rooms to make your stay comfortable and enjoyable.  

            Our Standard Rooms are cozy and have everything you need for a good rest. If you want more space and style, our Deluxe Rooms offer extra comfort and nice views. For a truly special stay, our Suites provide a large living area and premium services. We also have Family Rooms with extra space for larger groups.  
            
            No matter which room you choose, you will enjoy a clean, peaceful, and relaxing stay. Book with us today for a great experience!</p>
        <div class="list-of-rooms">
            <div class="room-components"><img src="images/pexels-aftabmirza-30761844.jpg" alt="" class="rooms-image">
            <h1 class="title">Standard Room</h1><br><div class="sub-header">
                <button>Book Rooms</button>
            </div></div>
            <div class="room-components"><img src="images/pexels-cottonbro-6466285.jpg" alt="" class="rooms-image">
                <h1 class="title">Deluxe Room</h1><br><div class="sub-header">
                    <button>Book Rooms</button>
                </div></div>
            <div class="room-components"><img src="images/pexels-aksinfo7-30808019.jpg" alt="" class="rooms-image">
                <h1 class="title">Suite</h1> <br><div class="sub-header">
                    <button>Book Rooms</button>
                </div></div>
            <div class="room-components"><img src="images/pexels-olly-3139124.jpg" alt="" class="rooms-image">
                <h1 class="title">Family Room</h1><br><div class="sub-header">
                    <button>Book Rooms</button>
                </div></div>
            <div class="room-components"><img src="images/pexels-pixabay-271624.jpg" alt="" class="rooms-image">
                <h1 class="title">Executive Room</h1><br><div class="sub-header">
                    <button>Book Rooms</button>
                </div></div>
            <div class="room-components"><img src="images/pexels-rana-matloob-hussain-733235258-26886879.jpg" alt="" class="rooms-image">
                <h1 class="title">Presidential Suite </h1><br><div class="sub-header">
                    <button>Book Rooms</button>
                </div></div>
        </div>
    </div>


    <div class="services" id="services">

        <h1>
            Services we Provide
        </h1>
        <p>At Luxury Hotel, we offer the following services to make your stay enjoyable. Please note that guests can book only the room, and additional services may be available at extra cost.</p>
        <div class="sub-services">
            <div class="component-of-service">
                <img src="images/pexels-cottonbro-6466282.jpg" alt="" class="img-service">
                <h1 class="title">24/7 Room Service</h1>
            </div>
            <div class="component-of-service">
                <img src="images/pexels-rdne-7563691.jpg" alt="" class="img-service">
                <h1 class="title">Free Wi-Fi</h1>
            </div>
            <div class="component-of-service">
                <img src="images/pexels-pixabay-261108.jpg" alt="" class="img-service">
                <h1 class="title">Swimming Pool </h1>
            </div>
            <div class="component-of-service">
                <img src="images/pexels-olly-3836861.jpg" alt="" class="img-service">
                <h1 class="title">Fitness Center</h1>
            </div>
            <div class="component-of-service">
                <img src="images/pexels-szymon-shields-1503561-29033154.jpg" alt="" class="img-service">
                <h1 class="title">Restaurant & Café</h1>
            </div>
            <div class="component-of-service">
                <img src="images/pexels-badun-16551615.jpg" alt="" class="img-service">
                <h1 class="title">Parking </h1>
            </div>
           
        </div>
        <p>
            We are dedicated to providing excellent service to make your stay truly special!
        </p>
    </div>
    

    

    <div class="Hotel-team">
    <h1>
        Introduce Our Team
    </h1>
    <div class="team-img">
        <img src="images/photo-1589386417686-0d34b5903d23.avif " alt="team-img" class="team">
        <h1 class="title">Kenean Dita</h1>
        <h2></h2>
    </div>
    <div class="team-img">
        <img src="images/Corporate Pictures.jpg " alt="team-img" class="team">
        <h1 class="title">Atinaf Bedasa</h1>
        <h2></h2>
    </div>
    <div class="team-img">
        <img src="images/Corporate Pictures.jpg " alt="team-img" class="team">
        <h1 class="title">Mesfin Ayele</h1>
        <h2></h2>
    </div>
    <div class="team-img">
        <img src="images/istockphoto-476773822-612x612.jpg" alt="team-img" class="team">
        <h1 class="title">Kidist Teshome</h1>
        <h2></h2>
    </div>
    <div class="team-img">
        <img src="images/Corporate Headshot Poses.jpg " alt="team-img" class="team">
        <h1 class="title">Dawit Mengesha</h1>
        <h2></h2>
    </div>
</div>
    <div class="contact-us" id="contact-us">
        <h1>Contact Us</h1>
        <div class="contact-form">
            <form action="" method="post">
            <label for="name">Full Name : </label><input type="text" name="name" placeholder="eg.abebe"><br>
            <label for="email">Email : </label><input type="email" name="c_email" placeholder="eg.john@gmail.com"><br>
            <label for="phone">Phone : </label><input type="tel" name="c_phone" placeholder="eg.0957568234"> <br>
            <label for="comment">Comment : </label><input type="text" name="comment" placeholder="  comment....."><br>
            <textarea name="additional" id="textarea" rows="10" name="message" placeholder="Additional Message"></textarea><br>
            <input type="submit">
            </form>
        </div>
        
    </div>

    <div class="guest-rate">
<h1>Comments</h1>
       <div class="rating-box">
        <h1></h1>
        <div class="guest-profile">
            <img src="images/istockphoto-476773822-612x612.jpg" alt="guest image">
            <h1>latera tujo</h1>
        </div>
        <div class="comment">
            
            <p>hey there i didnt saw such like hotel before now. it's luxury ,safety , comfort, and simplicity is amazing</p>
        </div>
       </div>
    </div>

    <footer>
        <div></div>
        <div></div>
        <div></div>
        
<p>2025 &copy;Copy right Reserved, Luxury Hotel</p>
    </footer>
    <script>
        function toggleMenu() {
            document.querySelector('.header-list').classList.toggle('active');
        }
    </script>
</body>
</html>