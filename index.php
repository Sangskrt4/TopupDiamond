<?php
// File: index.php
require_once __DIR__ . '/config/database.php'; 
require_once __DIR__ . '/config/helpers.php';   

// Inisialisasi koneksi database
$database = new Database();
$conn = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle login
    if (isset($_POST['login'])) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = sanitizeInput($_POST['password'] ?? '');
        
        // Validasi input
        if (empty($username) || empty($password)) {
            showMessage('Username dan password harus diisi!', 'error');
            redirect('index.php');
        }
        
        $query = "SELECT * FROM users WHERE username = ? AND password = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Update last login
            $update_query = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            showMessage('Login berhasil! Selamat datang ' . htmlspecialchars($user['username']), 'success');
            redirect('index.php');
        } else {
            showMessage('Username atau password salah!', 'error');
        }
    }
    
    // Handle register
    if (isset($_POST['register'])) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = sanitizeInput($_POST['password'] ?? '');
        
        // Validasi input
        if (empty($username) || empty($email) || empty($password)) {
            showMessage('Semua field harus diisi!', 'error');
            redirect('index.php');
        }
        
        if (!validateUsername($username)) {
            showMessage('Username harus 3-20 karakter (huruf, angka, underscore)', 'error');
            redirect('index.php');
        }
        
        if (!validateEmail($email)) {
            showMessage('Format email tidak valid!', 'error');
            redirect('index.php');
        }
        
        if (!validatePassword($password)) {
            showMessage('Password minimal 6 karakter!', 'error');
            redirect('index.php');
        }
        
        // Check if user exists
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            showMessage('Username atau email sudah digunakan!', 'error');
        } else {
            // Insert new user
            $insert_query = "INSERT INTO users (username, email, password, is_admin, created_at) 
                           VALUES (?, ?, ?, FALSE, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sss", $username, $email, $password);
            
            if ($insert_stmt->execute()) {
                $user_id = $insert_stmt->insert_id;
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = false;
                
                showMessage('Registrasi berhasil! Selamat bergabung ' . htmlspecialchars($username), 'success');
                redirect('index.php');
            } else {
                showMessage('Gagal melakukan registrasi', 'error');
            }
        }
        redirect('index.php');
    }
    
    // Handle payment/transaction
    if (isset($_POST['process_payment'])) {
        if (!isLoggedIn()) {
            showMessage('Silakan login terlebih dahulu!', 'error');
            redirect('index.php');
        }
        
        $package_id = (int) sanitizeInput($_POST['package_id'] ?? 0);
        $player_id = sanitizeInput($_POST['player_id'] ?? '');
        $player_name = sanitizeInput($_POST['player_name'] ?? '');
        $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
        
        // Validasi input
        if ($package_id <= 0) {
            showMessage('Paket tidak valid!', 'error');
            redirect('index.php');
        }
        
        if (empty($player_id) || empty($player_name) || empty($payment_method)) {
            showMessage('Harap isi semua field pembayaran!', 'error');
            redirect('index.php');
        }
        
        // Get package info
        $package_query = "SELECT p.*, g.id as game_id FROM packages p 
                         JOIN games g ON p.game_id = g.id 
                         WHERE p.id = ?";
        $package_stmt = $conn->prepare($package_query);
        $package_stmt->bind_param("i", $package_id);
        $package_stmt->execute();
        $package_result = $package_stmt->get_result();
        $package = $package_result->fetch_assoc();
        
        if ($package) {
            // Create transaction
            $transaction_query = "INSERT INTO transactions 
                                (user_id, game_id, package_id, player_id, player_name, 
                                 payment_method, price, status, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $transaction_stmt = $conn->prepare($transaction_query);
            $transaction_stmt->bind_param("iiisssd", 
                $_SESSION['user_id'],
                $package['game_id'],
                $package_id,
                $player_id,
                $player_name,
                $payment_method,
                $package['price']
            );
            
            if ($transaction_stmt->execute()) {
                showMessage('Transaksi berhasil! Tunggu konfirmasi admin.', 'success');
            } else {
                showMessage('Gagal membuat transaksi', 'error');
            }
        } else {
            showMessage('Paket tidak ditemukan', 'error');
        }
        
        redirect('index.php');
    }
}

// Get current user data
$logged_in = isLoggedIn();
$current_user = null;
$user_transactions = [];

if ($logged_in) {
    // Get user info
    $user_query = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $current_user = $user_result->fetch_assoc();
    
    // Get user's transactions (UNTUK USER BIASA, tidak ada filter)
    $transaction_query = "SELECT t.*, g.game_code, g.game_name, p.package_name 
                        FROM transactions t
                        JOIN games g ON t.game_id = g.id
                        JOIN packages p ON t.package_id = p.id
                        WHERE t.user_id = ?
                        ORDER BY t.created_at DESC";
    $transaction_stmt = $conn->prepare($transaction_query);
    $transaction_stmt->bind_param("i", $_SESSION['user_id']);
    
    $transaction_stmt->execute();
    $transaction_result = $transaction_stmt->get_result();
    
    while ($row = $transaction_result->fetch_assoc()) {
        $user_transactions[] = $row;
    }
}

// Get all games
$games = [];
$games_query = "SELECT * FROM games WHERE is_active = TRUE";
$games_result = $conn->query($games_query);
while ($row = $games_result->fetch_assoc()) {
    $games[] = $row;
}

// Get packages for each game
$ml_packages = [];
$roblox_packages = [];
$freefire_packages = [];

// Mobile Legends packages
$ml_query = "SELECT p.*, g.game_code FROM packages p 
            JOIN games g ON p.game_id = g.id
            WHERE g.game_code = 'ml' ORDER BY p.price ASC";
$ml_result = $conn->query($ml_query);
while ($row = $ml_result->fetch_assoc()) {
    $ml_packages[] = $row;
}

// Roblox packages
$roblox_query = "SELECT p.*, g.game_code FROM packages p 
                JOIN games g ON p.game_id = g.id
                WHERE g.game_code = 'roblox' ORDER BY p.price ASC";
$roblox_result = $conn->query($roblox_query);
while ($row = $roblox_result->fetch_assoc()) {
    $roblox_packages[] = $row;
}

// Free Fire packages
$freefire_query = "SELECT p.*, g.game_code FROM packages p 
                  JOIN games g ON p.game_id = g.id
                  WHERE g.game_code = 'freefire' ORDER BY p.price ASC";
$freefire_result = $conn->query($freefire_query);
while ($row = $freefire_result->fetch_assoc()) {
    $freefire_packages[] = $row;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}

// Get message from session
$message_data = getMessage();
$message = $message_data['message'] ?? '';
$message_type = $message_data['type'] ?? '';

// Check if package is selected for auto-scroll
$selected_package = null;
if (isset($_POST['package_id'])) {
    $package_id = (int) sanitizeInput($_POST['package_id']);
    $package_query = "SELECT p.*, g.game_code, g.game_name FROM packages p 
                    JOIN games g ON p.game_id = g.id 
                    WHERE p.id = ?";
    $package_stmt = $conn->prepare($package_query);
    $package_stmt->bind_param("i", $package_id);
    $package_stmt->execute();
    $selected_package = $package_stmt->get_result()->fetch_assoc();
}

// Get user avatar info
$user_avatar = null;
if ($logged_in && $current_user) {
    $user_avatar = getUserAvatar($current_user['username']);
}

// Get filter for admin history
$filter = $_GET['filter'] ?? 'all';
if ($current_user && $current_user['is_admin'] && $logged_in) {
    // For admin, apply filter to user_transactions
    $admin_transaction_query = "SELECT t.*, g.game_code, g.game_name, p.package_name 
                               FROM transactions t
                               JOIN games g ON t.game_id = g.id
                               JOIN packages p ON t.package_id = p.id
                               WHERE 1=1";
    
    if ($filter !== 'all') {
        $admin_transaction_query .= " AND t.status = ?";
    }
    
    $admin_transaction_query .= " ORDER BY t.created_at DESC";
    
    $admin_transaction_stmt = $conn->prepare($admin_transaction_query);
    
    if ($filter !== 'all') {
        $admin_transaction_stmt->bind_param("s", $filter);
    }
    
    $admin_transaction_stmt->execute();
    $admin_transaction_result = $admin_transaction_stmt->get_result();
    $user_transactions = [];
    
    while ($row = $admin_transaction_result->fetch_assoc()) {
        $user_transactions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameTopUp - Top Up Game Favorit Anda</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .notification {
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        max-width: 300px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        transform: translateX(400px);
        transition: transform 0.3s ease;
        cursor: pointer;
    }
    .notification.success {
        background-color: #00b894;
    }
    .notification.error {
        background-color: #e17055;
    }
    .notification.info {
        background-color: #6c5ce7;
    }
    .system-notification {
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        max-width: 300px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        animation: slideIn 0.3s ease;
    }
    .system-notification.success {
        background-color: #00b894;
    }
    .system-notification.error {
        background-color: #e17055;
    }
    .system-notification.info {
        background-color: #6c5ce7;
    }
    @keyframes slideIn {
        from { transform: translateX(400px); }
        to { transform: translateX(0); }
    }
    
    /* FIX untuk filter buttons di history */
    .history-filters {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 10px;
        padding: 20px;
        background: var(--dark-card);
        border-radius: 10px;
        border: 1px solid var(--dark-border);
    }
    
    .filter-btn {
        padding: 10px 20px;
        background-color: var(--dark-bg);
        border: 1px solid var(--dark-border);
        border-radius: 6px;
        color: var(--dark-text);
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        min-width: 120px;
        justify-content: center;
    }
    
    .filter-btn.active {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
    }
    
    .filter-btn:hover:not(.active) {
        background-color: rgba(108, 92, 231, 0.1);
        border-color: var(--primary-color);
    }
    
    /* FIX untuk history section */
    .history-section {
        scroll-margin-top: 100px;
    }
    
    /* FIX untuk user dropdown */
    .user-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .user-menu-btn {
        background: transparent;
        border: none;
        color: var(--dark-text);
        padding: 8px 15px;
        font-size: 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.3s;
        min-height: 40px;
    }
    
    .user-menu-btn:hover {
        background-color: rgba(108, 92, 231, 0.1);
        color: var(--primary-color);
    }
    
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background-color: var(--dark-card);
        min-width: 200px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        border-radius: 8px;
        border: 1px solid var(--dark-border);
        overflow: hidden;
        margin-top: 5px;
    }
    
    .dropdown-content.show {
        display: block;
        animation: fadeIn 0.2s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>
</head>
<body>
    <!-- System Notification -->
    <?php if ($message): ?>
    <div class="system-notification <?php echo $message_type; ?>" id="systemNotification">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <script>
    setTimeout(() => {
        const notification = document.getElementById('systemNotification');
        if (notification) {
            notification.style.display = 'none';
        }
    }, 3000);
    </script>
    <?php endif; ?>

    <!-- Header & Navigasi -->
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2><i class="fas fa-gamepad"></i> Game<span>TopUp</span></h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="#home" class="nav-link"><i class="fas fa-home"></i> Beranda</a></li>
                    <li><a href="#games" class="nav-link"><i class="fas fa-gamepad"></i> Game</a></li>
                    <li><a href="#topup" class="nav-link"><i class="fas fa-shopping-cart"></i> Top Up</a></li>
                    <li><a href="#history" class="nav-link"><i class="fas fa-history"></i> Riwayat</a></li>
                    <li>
                        <!-- User Dropdown Menu - FIXED -->
                        <div class="user-dropdown">
                            <button class="user-menu-btn" id="userMenuBtn">
                                <?php if ($logged_in && $current_user): ?>
                                <div class="user-info">
                                    <div class="user-avatar" style="background-color: <?php echo $user_avatar['color']; ?>;">
                                        <?php echo $user_avatar['initial']; ?>
                                    </div>
                                    <div class="user-name">
                                        <?php echo htmlspecialchars($current_user['username']); ?>
                                        <?php if ($current_user['is_admin']): ?>
                                        <span class="admin-badge">ADMIN</span>
                                        <?php endif; ?>
                                    </div>
                                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                                </div>
                                <?php else: ?>
                                <div class="user-info">
                                    <i class="fas fa-user"></i> 
                                    <span>Login</span>
                                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                                </div>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-content" id="dropdownMenu">
                                <?php if ($logged_in && $current_user): ?>
                                <a href="profil.php" id="profilLink"><i class="fas fa-user-circle"></i> Profil</a>
                                <?php if ($current_user['is_admin']): ?>
                                <a href="admin.php" id="adminLink"><i class="fas fa-cog"></i> Admin Panel</a>
                                <?php endif; ?>
                                <a href="?logout=1" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a>
                                <?php else: ?>
                                <a href="#" id="loginLink"><i class="fas fa-sign-in-alt"></i> Login</a>
                                <a href="#" id="registerLink"><i class="fas fa-user-plus"></i> Daftar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Modal Login -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><i class="fas fa-user-circle"></i> Login ke Akun Anda</h2>
            <form id="loginForm" method="POST" action="">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Masukkan username" maxlength="20">
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Masukkan password" minlength="6">
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-sign-in-alt"></i> Login</button>
                <p>Belum punya akun? <a href="#" id="showRegister">Daftar di sini</a></p>
            </form>
            
            <form id="registerForm" class="hidden" method="POST" action="">
                <input type="hidden" name="register" value="1">
                <div class="form-group">
                    <label for="regUsername"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="regUsername" name="username" required 
                           placeholder="Masukkan username (3-20 karakter)" 
                           minlength="3" maxlength="20"
                           pattern="[a-zA-Z0-9_]+"
                           title="Hanya huruf, angka, dan underscore">
                </div>
                <div class="form-group">
                    <label for="regEmail"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="regEmail" name="email" required 
                           placeholder="Masukkan email">
                </div>
                <div class="form-group">
                    <label for="regPassword"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="regPassword" name="password" required 
                           placeholder="Minimal 6 karakter" minlength="6">
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-user-plus"></i> Daftar</button>
                <p>Sudah punya akun? <a href="#" id="showLogin">Login di sini</a></p>
            </form>
        </div>
    </div>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1>Top Up Game Favorit Anda</h1>
            <p>Dapatkan diamond, robux, dan item game lainnya dengan harga terbaik dan proses cepat</p>
            <a href="#games" class="btn-primary"><i class="fas fa-gamepad"></i> Lihat Game</a>
        </div>
        <div class="hero-image">
            <img src="img/logogame.png" 
                 alt="Game Top Up" loading="lazy">
        </div>
    </section>

    <!-- Games Section - DIUPDATE DENGAN DESAIN BARU -->
    <section id="games" class="games-section">
        <div class="container">
            <h2 class="section-title">Pilih Game Favorit Anda</h2>
            <p class="section-subtitle">Klik game favorit Anda untuk melihat paket top up yang tersedia</p>
            
            <div class="games-grid-new">
                <?php foreach ($games as $index => $game): 
                    // Tentukan gambar berdasarkan game
                    $game_images = [
                        'ml' => 'img/logomiya.jpg',
                        'roblox' => 'img/ROBLOX.jpg',
                        'freefire' => 'img/garena.jpg'
                    ];
                    $game_image = $game_images[$game['game_code']] ?? 'img/logogame.png';
                ?>
                <div class="game-card-new" data-game="<?php echo htmlspecialchars($game['game_code']); ?>">
                    <div class="game-card-inner">
                        <div class="game-card-front">
                            <div class="game-icon-new">
                                <img src="<?php echo $game_image; ?>" 
                                     alt="<?php echo htmlspecialchars($game['game_name']); ?>" loading="lazy">
                                <div class="game-overlay">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                            </div>
                            <h3><?php echo htmlspecialchars($game['game_name']); ?></h3>
                            <p>Top Up <?php echo htmlspecialchars($game['game_name']); ?> dengan harga terbaik</p>
                            <div class="game-badge-new">
                                <span class="badge-new">Populer</span>
                            </div>
                            <button class="btn-select-game" data-game="<?php echo htmlspecialchars($game['game_code']); ?>">
                                <i class="fas fa-arrow-right"></i> Pilih Game
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Top Up Section - DIUPDATE DENGAN DESAIN BARU -->
    <section id="topup" class="topup-section-new">
        <div class="container">
            <div class="topup-header">
                <h2 class="section-title" id="topup-title">Pilih Nominal Top Up</h2>
                <p class="topup-subtitle" id="topup-subtitle">Silakan pilih game terlebih dahulu</p>
            </div>
            
            <!-- Game Selection Horizontal - DIUPDATE -->
            <div class="game-selection-new">
                <div class="game-option-new active" data-game="ml">
                    <div class="game-option-icon">
                        <img src="img/logomiya.jpg" alt="Mobile Legends" loading="lazy">
                    </div>
                    <div class="game-option-info">
                        <span>Mobile Legends</span>
                        <small>Diamond & Skin</small>
                    </div>
                    <div class="game-option-check">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="game-option-new" data-game="roblox">
                    <div class="game-option-icon">
                        <img src="img/ROBLOX.jpg" alt="Roblox" loading="lazy">
                    </div>
                    <div class="game-option-info">
                        <span>Roblox</span>
                        <small>Robux & Gamepass</small>
                    </div>
                    <div class="game-option-check">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="game-option-new" data-game="freefire">
                    <div class="game-option-icon">
                        <img src="img/garena.jpg" alt="Free Fire" loading="lazy">
                    </div>
                    <div class="game-option-info">
                        <span>Free Fire</span>
                        <small>Diamond & Voucher</small>
                    </div>
                    <div class="game-option-check">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
            
            <!-- Top Up Packages - Tampilan Grid Modern -->
            <div class="packages-container-new">
                <!-- Mobile Legends packages -->
                <div class="package-category-new active" id="ml-packages-new">
                    <div class="package-header-new">
                        <div class="package-game-info">
                            <div class="package-game-icon">
                                <img src="img/logomiya.jpg" alt="Mobile Legends" loading="lazy">
                            </div>
                            <div>
                                <h3><i class="fas fa-gem"></i> Paket Diamond Mobile Legends</h3>
                                <p>Pilih paket diamond yang sesuai dengan kebutuhan Anda</p>
                            </div>
                        </div>
                    </div>
                    <div class="packages-grid-new">
                        <?php foreach ($ml_packages as $package): ?>
                        <div class="package-card-new">
                            <div class="package-badge-new <?php echo !empty($package['badge']) ? 'has-badge' : ''; ?>">
                                <?php if (!empty($package['badge'])): ?>
                                <span class="badge-label"><?php echo htmlspecialchars($package['badge']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="package-content-new">
                                <h4><?php echo htmlspecialchars($package['package_name']); ?></h4>
                                <div class="package-price-new">
                                    <span class="price-label">Harga</span>
                                    <span class="price-value"><?php echo formatPrice($package['price']); ?></span>
                                </div>
                                <div class="package-features">
                                    <span><i class="fas fa-bolt"></i> Proses Cepat</span>
                                    <span><i class="fas fa-shield-alt"></i> 100% Aman</span>
                                </div>
                            </div>
                            <button class="btn-select-package select-package-new"
                                    data-package-id="<?php echo $package['id']; ?>"
                                    data-package-name="<?php echo htmlspecialchars($package['package_name']); ?>"
                                    data-price="<?php echo $package['price']; ?>"
                                    data-game="ml">
                                <i class="fas fa-shopping-cart"></i> 
                                <span>Pilih Paket</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Roblox packages -->
                <div class="package-category-new" id="roblox-packages-new">
                    <div class="package-header-new">
                        <div class="package-game-info">
                            <div class="package-game-icon">
                                <img src="img/ROBLOX.jpg" alt="Roblox" loading="lazy">
                            </div>
                            <div>
                                <h3><i class="fas fa-cube"></i> Paket Robux Roblox</h3>
                                <p>Pilih paket robux yang sesuai dengan kebutuhan Anda</p>
                            </div>
                        </div>
                    </div>
                    <div class="packages-grid-new">
                        <?php foreach ($roblox_packages as $package): ?>
                        <div class="package-card-new">
                            <div class="package-badge-new <?php echo !empty($package['badge']) ? 'has-badge' : ''; ?>">
                                <?php if (!empty($package['badge'])): ?>
                                <span class="badge-label"><?php echo htmlspecialchars($package['badge']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="package-content-new">
                                <h4><?php echo htmlspecialchars($package['package_name']); ?></h4>
                                <div class="package-price-new">
                                    <span class="price-label">Harga</span>
                                    <span class="price-value"><?php echo formatPrice($package['price']); ?></span>
                                </div>
                                <div class="package-features">
                                    <span><i class="fas fa-bolt"></i> Proses Cepat</span>
                                    <span><i class="fas fa-shield-alt"></i> 100% Aman</span>
                                </div>
                            </div>
                            <button class="btn-select-package select-package-new"
                                    data-package-id="<?php echo $package['id']; ?>"
                                    data-package-name="<?php echo htmlspecialchars($package['package_name']); ?>"
                                    data-price="<?php echo $package['price']; ?>"
                                    data-game="roblox">
                                <i class="fas fa-shopping-cart"></i> 
                                <span>Pilih Paket</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Free Fire packages -->
                <div class="package-category-new" id="freefire-packages-new">
                    <div class="package-header-new">
                        <div class="package-game-info">
                            <div class="package-game-icon">
                                <img src="img/garena.jpg" alt="Free Fire" loading="lazy">
                            </div>
                            <div>
                                <h3><i class="fas fa-fire"></i> Paket Diamond Free Fire</h3>
                                <p>Pilih paket diamond yang sesuai dengan kebutuhan Anda</p>
                            </div>
                        </div>
                    </div>
                    <div class="packages-grid-new">
                        <?php foreach ($freefire_packages as $package): ?>
                        <div class="package-card-new">
                            <div class="package-badge-new <?php echo !empty($package['badge']) ? 'has-badge' : ''; ?>">
                                <?php if (!empty($package['badge'])): ?>
                                <span class="badge-label"><?php echo htmlspecialchars($package['badge']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="package-content-new">
                                <h4><?php echo htmlspecialchars($package['package_name']); ?></h4>
                                <div class="package-price-new">
                                    <span class="price-label">Harga</span>
                                    <span class="price-value"><?php echo formatPrice($package['price']); ?></span>
                                </div>
                                <div class="package-features">
                                    <span><i class="fas fa-bolt"></i> Proses Cepat</span>
                                    <span><i class="fas fa-shield-alt"></i> 100% Aman</span>
                                </div>
                            </div>
                            <button class="btn-select-package select-package-new"
                                    data-package-id="<?php echo $package['id']; ?>"
                                    data-package-name="<?php echo htmlspecialchars($package['package_name']); ?>"
                                    data-price="<?php echo $package['price']; ?>"
                                    data-game="freefire">
                                <i class="fas fa-shopping-cart"></i> 
                                <span>Pilih Paket</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Payment Section akan muncul di sini setelah paket dipilih -->
            <div id="payment-preview" class="payment-preview hidden">
                <div class="payment-preview-content">
                    <div class="payment-preview-header">
                        <h3><i class="fas fa-credit-card"></i> Lanjutkan ke Pembayaran</h3>
                        <button class="btn-close-preview"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="payment-preview-body">
                        <div class="preview-summary">
                            <h4>Ringkasan Pesanan</h4>
                            <div class="preview-details">
                                <div class="preview-item">
                                    <span>Game:</span>
                                    <strong id="preview-game">Mobile Legends</strong>
                                </div>
                                <div class="preview-item">
                                    <span>Paket:</span>
                                    <strong id="preview-package">86 Diamond</strong>
                                </div>
                                <div class="preview-item">
                                    <span>Harga:</span>
                                    <strong class="price-highlight" id="preview-price">Rp 25.000</strong>
                                </div>
                            </div>
                        </div>
                        <div class="preview-actions">
                            <button class="btn-secondary" id="btn-back-to-packages">
                                <i class="fas fa-arrow-left"></i> Kembali ke Paket
                            </button>
                            <button class="btn-primary" id="btn-proceed-to-payment">
                                <i class="fas fa-lock"></i> Lanjutkan Pembayaran
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Payment Form (akan muncul ketika paket dipilih) -->
    <section id="payment" class="payment-section <?php echo isset($_POST['package_id']) ? '' : 'hidden'; ?>">
        <div class="container">
            <h2 class="section-title">Form Pembayaran</h2>
            <div class="payment-container">
                <div class="payment-summary">
                    <h3><i class="fas fa-receipt"></i> Ringkasan Pesanan</h3>
                    <div class="summary-details">
                        <?php if ($selected_package): ?>
                        <p><strong>Game:</strong> <span id="summary-game">
                            <?php echo getGameName($selected_package['game_code']); ?>
                        </span></p>
                        <p><strong>Paket:</strong> <span id="summary-package">
                            <?php echo htmlspecialchars($selected_package['package_name']); ?>
                        </span></p>
                        <p><strong>Harga:</strong> <span id="summary-price">
                            <?php echo formatPrice($selected_package['price']); ?>
                        </span></p>
                        <p><strong>Status:</strong> <span class="status pending">Menunggu Pembayaran</span></p>
                        <?php else: ?>
                        <p>Pilih paket terlebih dahulu</p>
                        <?php endif; ?>
                    </div>
                </div>
                <form id="paymentForm" method="POST" action="" class="payment-form">
                    <input type="hidden" name="process_payment" value="1">
                    <input type="hidden" name="package_id" id="selectedPackageId" 
                           value="<?php echo $_POST['package_id'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label for="playerId"><i class="fas fa-id-card"></i> ID Player</label>
                        <input type="text" id="playerId" name="player_id" required 
                               value="<?php echo $_POST['player_id'] ?? ''; ?>"
                               placeholder="Contoh: 123456789" maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label for="playerName"><i class="fas fa-user"></i> Nama Player</label>
                        <input type="text" id="playerName" name="player_name" required 
                               value="<?php echo $_POST['player_name'] ?? ''; ?>"
                               placeholder="Nama karakter dalam game" maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentMethod"><i class="fas fa-credit-card"></i> Metode Pembayaran</label>
                        <select id="paymentMethod" name="payment_method" required>
                            <option value="">Pilih Metode</option>
                            <option value="gopay" <?php echo ($_POST['payment_method'] ?? '') == 'gopay' ? 'selected' : ''; ?>>GoPay</option>
                            <option value="ovo" <?php echo ($_POST['payment_method'] ?? '') == 'ovo' ? 'selected' : ''; ?>>OVO</option>
                            <option value="dana" <?php echo ($_POST['payment_method'] ?? '') == 'dana' ? 'selected' : ''; ?>>DANA</option>
                            <option value="bank" <?php echo ($_POST['payment_method'] ?? '') == 'bank' ? 'selected' : ''; ?>>Transfer Bank</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary"><i class="fas fa-lock"></i> Bayar Sekarang</button>
                </form>
            </div>
        </div>
    </section>

    <!-- History Section - FIXED SCROLL BUG -->
    <section id="history" class="history-section">
        <div class="container">
            <h2 class="section-title">Riwayat Top Up</h2>
            <div class="history-container">
                <?php if (!$logged_in): ?>
                <!-- Tampilkan pesan login required -->
                <div class="login-required" style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
                    <i class="fas fa-user-circle" style="font-size: 48px; color: #6c757d;"></i>
                    <p>Silakan login untuk melihat riwayat transaksi</p>
                    <button class="btn-primary" onclick="openLoginModal()">Login Sekarang</button>
                </div>
                <?php else: ?>
                <!-- Tampilkan riwayat transaksi -->
                <?php if ($current_user && $current_user['is_admin']): ?>
                <!-- ADMIN melihat semua dengan filter - FIXED SCROLL BUG -->
                <div class="history-filters">
                    <a href="index.php?filter=all#history" class="filter-btn <?php echo ($filter ?? 'all') == 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> Semua
                    </a>
                    <a href="index.php?filter=pending#history" class="filter-btn <?php echo ($filter ?? '') == 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Pending
                    </a>
                    <a href="index.php?filter=success#history" class="filter-btn <?php echo ($filter ?? '') == 'success' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Berhasil
                    </a>
                    <a href="index.php?filter=failed#history" class="filter-btn <?php echo ($filter ?? '') == 'failed' ? 'active' : ''; ?>">
                        <i class="fas fa-times-circle"></i> Gagal
                    </a>
                </div>
                <?php else: ?>
                <!-- USER BIASA melihat tanpa filter -->
                <div class="history-info">
                    <i class="fas fa-info-circle"></i>
                    <p>Anda dapat melihat riwayat transaksi Anda di bawah ini.</p>
                </div>
                <?php endif; ?>
                
                <div class="history-table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar"></i> Tanggal</th>
                                <th><i class="fas fa-gamepad"></i> Game</th>
                                <th><i class="fas fa-box"></i> Paket</th>
                                <th><i class="fas fa-id-card"></i> ID Player</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($user_transactions)): ?>
                            <tr>
                                <td colspan="5" class="no-transactions">
                                    <p><i class="fas fa-receipt"></i></p>
                                    <p>Belum ada riwayat transaksi</p>
                                    <small>Mulai lakukan top up pertama Anda!</small>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($user_transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <?php echo formatDateTime($transaction['created_at'], 'd/m/Y'); ?><br>
                                    <small><?php echo formatDateTime($transaction['created_at'], 'H:i'); ?></small>
                                </td>
                                <td><?php echo getGameName($transaction['game_code']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['package_name']); ?></td>
                                <td>
                                    <?php 
                                    // Sensor ID Player untuk user biasa, admin bisa lihat lengkap
                                    echo maskPlayerId(
                                        $transaction['player_id'], 
                                        $current_user && $current_user['is_admin']
                                    );
                                    ?>
                                </td>
                                <td>
                                    <?php echo getStatusBadge($transaction['status']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-gamepad"></i> GameTopUp</h3>
                    <p>Platform top up game terpercaya dengan proses cepat dan aman.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4><i class="fas fa-gamepad"></i> Layanan</h4>
                    <ul>
                        <li><a href="#games"><i class="fas fa-mobile-alt"></i> Mobile Legends</a></li>
                        <li><a href="#games"><i class="fas fa-cube"></i> Roblox</a></li>
                        <li><a href="#games"><i class="fas fa-fire"></i> Free Fire</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4><i class="fas fa-question-circle"></i> Bantuan</h4>
                    <ul>
                        <li><a href="#"><i class="fas fa-book"></i> Cara Top Up</a></li>
                        <li><a href="#"><i class="fas fa-question"></i> FAQ</a></li>
                        <li><a href="#"><i class="fas fa-phone"></i> Hubungi Kami</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4><i class="fas fa-user"></i> Akun</h4>
                    <ul>
                        <?php if (!$logged_in): ?>
                        <li><a href="#" id="footerLoginBtn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="#" id="footerRegisterBtn"><i class="fas fa-user-plus"></i> Daftar</a></li>
                        <?php else: ?>
                        <li><a href="profil.php"><i class="fas fa-user-circle"></i> Profil</a></li>
                        <li><a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> GameTopUp. All rights reserved. | 
                   <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
            </div>
        </div>
    </footer>

    <!-- Script -->
    <script src="script.js"></script>
    <script>