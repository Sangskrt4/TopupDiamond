<?php
// File: setup_database.php

// Konfigurasi database - SESUAIKAN DENGAN PORT ANDA!
$servername = "localhost:8111";  // Ganti :8111 dengan port MySQL Anda
$username = "root";
$password = "";  // Password MySQL Anda (default kosong)
$dbname = "gametopup_db";

echo "Mencoba koneksi ke MySQL...<br>";

// Koneksi ke MySQL TANPA DATABASE dulu
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error . "<br>" . 
        "Pastikan MySQL berjalan di port yang benar!");
}

echo "Koneksi MySQL berhasil!<br>";
echo "Membuat database dan tabel...<br>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database '$dbname' berhasil dibuat<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db($dbname);

// Tabel users
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_admin BOOLEAN DEFAULT FALSE
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel 'users' berhasil dibuat<br>";
} else {
    echo "Error creating table users: " . $conn->error . "<br>";
}

// Tabel games
$sql = "CREATE TABLE IF NOT EXISTS games (
    id INT PRIMARY KEY AUTO_INCREMENT,
    game_code VARCHAR(20) UNIQUE NOT NULL,
    game_name VARCHAR(100) NOT NULL,
    game_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel 'games' berhasil dibuat<br>";
} else {
    echo "Error creating table games: " . $conn->error . "<br>";
}

// Tabel packages
$sql = "CREATE TABLE IF NOT EXISTS packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    package_amount VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    badge VARCHAR(20),
    is_best_seller BOOLEAN DEFAULT FALSE
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel 'packages' berhasil dibuat<br>";
} else {
    echo "Error creating table packages: " . $conn->error . "<br>";
}

// Tabel transactions
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    package_id INT NOT NULL,
    player_id VARCHAR(50) NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    price DECIMAL(10,2) NOT NULL,
    admin_notes TEXT,
    processed_at TIMESTAMP NULL,
    processed_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel 'transactions' berhasil dibuat<br>";
} else {
    echo "Error creating table transactions: " . $conn->error . "<br>";
}

// Insert admin user
$sql = "INSERT IGNORE INTO users (username, email, password, is_admin) 
        VALUES ('admin', 'admin@gametopup.com', 'admin123', TRUE)";
if ($conn->query($sql) === TRUE) {
    if ($conn->affected_rows > 0) {
        echo "User admin berhasil ditambahkan<br>";
    } else {
        echo "User admin sudah ada<br>";
    }
} else {
    echo "Error inserting admin: " . $conn->error . "<br>";
}

// Insert games data
$games = [
    ['ml', 'Mobile Legends'],
    ['roblox', 'Roblox'],
    ['freefire', 'Free Fire']
];

foreach ($games as $game) {
    $sql = "INSERT IGNORE INTO games (game_code, game_name) 
            VALUES ('{$game[0]}', '{$game[1]}')";
    $conn->query($sql);
}
echo "Data games berhasil ditambahkan<br>";

// Get game IDs
$game_ids = [];
$result = $conn->query("SELECT id, game_code FROM games");
while ($row = $result->fetch_assoc()) {
    $game_ids[$row['game_code']] = $row['id'];
}

// Insert packages for Mobile Legends
if (isset($game_ids['ml'])) {
    $ml_packages = [
        ['86 Diamond', '86 Diamond', 25000, 'Best Seller', TRUE],
        ['172 Diamond', '172 Diamond', 50000, NULL, FALSE],
        ['429 Diamond', '429 Diamond', 120000, NULL, FALSE],
        ['874 Diamond', '874 Diamond', 240000, 'Most Value', FALSE]
    ];
    
    foreach ($ml_packages as $package) {
        $sql = "INSERT IGNORE INTO packages (game_id, package_name, package_amount, price, badge, is_best_seller) 
                VALUES ({$game_ids['ml']}, '{$package[0]}', '{$package[1]}', {$package[2]}, " . 
                ($package[3] ? "'{$package[3]}'" : "NULL") . ", " . ($package[4] ? "TRUE" : "FALSE") . ")";
        $conn->query($sql);
    }
    echo "Paket Mobile Legends berhasil ditambahkan<br>";
}

// Insert packages for Roblox
if (isset($game_ids['roblox'])) {
    $roblox_packages = [
        ['80 Robux', '80 Robux', 15000, NULL, FALSE],
        ['400 Robux', '400 Robux', 60000, 'Best Seller', TRUE],
        ['800 Robux', '800 Robux', 120000, NULL, FALSE],
        ['2000 Robux', '2000 Robux', 300000, 'Most Value', FALSE]
    ];
    
    foreach ($roblox_packages as $package) {
        $sql = "INSERT IGNORE INTO packages (game_id, package_name, package_amount, price, badge, is_best_seller) 
                VALUES ({$game_ids['roblox']}, '{$package[0]}', '{$package[1]}', {$package[2]}, " . 
                ($package[3] ? "'{$package[3]}'" : "NULL") . ", " . ($package[4] ? "TRUE" : "FALSE") . ")";
        $conn->query($sql);
    }
    echo "Paket Roblox berhasil ditambahkan<br>";
}

// Insert packages for Free Fire
if (isset($game_ids['freefire'])) {
    $freefire_packages = [
        ['50 Diamond', '50 Diamond', 7000, NULL, FALSE],
        ['310 Diamond', '310 Diamond', 40000, 'Best Seller', TRUE],
        ['520 Diamond', '520 Diamond', 65000, NULL, FALSE],
        ['1060 Diamond', '1060 Diamond', 130000, 'Most Value', FALSE]
    ];
    
    foreach ($freefire_packages as $package) {
        $sql = "INSERT IGNORE INTO packages (game_id, package_name, package_amount, price, badge, is_best_seller) 
                VALUES ({$game_ids['freefire']}, '{$package[0]}', '{$package[1]}', {$package[2]}, " . 
                ($package[3] ? "'{$package[3]}'" : "NULL") . ", " . ($package[4] ? "TRUE" : "FALSE") . ")";
        $conn->query($sql);
    }
    echo "Paket Free Fire berhasil ditambahkan<br>";
}

echo "<br><h2>Setup database SELESAI!</h2>";
echo "<p>Database: <strong>$dbname</strong></p>";
echo "<p>Port MySQL: <strong>$servername</strong></p>";
echo "<p>Admin login:</p>";
echo "<ul>";
echo "<li>Username: <strong>admin</strong></li>";
echo "<li>Password: <strong>admin123</strong></li>";
echo "<li>Email: <strong>admin@gametopup.com</strong></li>";
echo "</ul>";
echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background: #6c5ce7; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;'>Klik di sini untuk ke Beranda</a>";

$conn->close();
?>