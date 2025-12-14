<?php
// File: profil.php
require_once 'config/database.php';
require_once 'config/helpers.php';

// Cek login
if (!isLoggedIn()) {
    showMessage('Silakan login terlebih dahulu!', 'error');
    redirect('index.php');
}

$database = new Database();
$conn = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();

if (!$current_user) {
    session_destroy();
    redirect('index.php');
}

// Tentukan judul halaman berdasarkan role
$page_title = $current_user['is_admin'] ? 'Profil Admin - GameTopUp' : 'Profil User - GameTopUp';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update profile
    if (isset($_POST['update_profile'])) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        
        // Check if username/email already exists (excluding current user)
        $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ssi", $username, $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            showMessage('Username atau email sudah digunakan!', 'error');
        } else {
            $update_query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssi", $username, $email, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['username'] = $username;
                showMessage('Profil berhasil diperbarui!', 'success');
                redirect('profil.php');
            } else {
                showMessage('Gagal memperbarui profil', 'error');
            }
        }
    }
    
    // Update password
    if (isset($_POST['update_password'])) {
        $current_password = sanitizeInput($_POST['current_password'] ?? '');
        $new_password = sanitizeInput($_POST['new_password'] ?? '');
        $confirm_password = sanitizeInput($_POST['confirm_password'] ?? '');
        
        // Verify current password
        if ($current_user['password'] === $current_password) {
            if ($new_password === $confirm_password) {
                $update_query = "UPDATE users SET password = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $new_password, $user_id);
                
                if ($update_stmt->execute()) {
                    showMessage('Password berhasil diperbarui!', 'success');
                } else {
                    showMessage('Gagal memperbarui password', 'error');
                }
            } else {
                showMessage('Password baru tidak cocok!', 'error');
            }
        } else {
            showMessage('Password saat ini salah!', 'error');
        }
        redirect('profil.php');
    }
}

// Get user's transactions for stats
$transaction_query = "SELECT status, COUNT(*) as count FROM transactions WHERE user_id = ? GROUP BY status";
$transaction_stmt = $conn->prepare($transaction_query);
$transaction_stmt->bind_param("i", $user_id);
$transaction_stmt->execute();
$transaction_result = $transaction_stmt->get_result();

$total_transactions = 0;
$success_transactions = 0;
$pending_transactions = 0;

while ($row = $transaction_result->fetch_assoc()) {
    $total_transactions += $row['count'];
    if ($row['status'] === 'success') {
        $success_transactions = $row['count'];
    } elseif ($row['status'] === 'pending') {
        $pending_transactions = $row['count'];
    }
}

// Get all transactions for table
$all_transactions_query = "SELECT t.*, g.game_code, g.game_name, p.package_name 
                          FROM transactions t
                          JOIN games g ON t.game_id = g.id
                          JOIN packages p ON t.package_id = p.id
                          WHERE t.user_id = ?
                          ORDER BY t.created_at DESC
                          LIMIT 10";
$all_transactions_stmt = $conn->prepare($all_transactions_query);
$all_transactions_stmt->bind_param("i", $user_id);
$all_transactions_stmt->execute();
$all_transactions_result = $all_transactions_stmt->get_result();
$user_transactions = [];

while ($row = $all_transactions_result->fetch_assoc()) {
    $user_transactions[] = $row;
}

// Get active tab
$active_tab = $_GET['tab'] ?? 'edit-profil';

// Get message
$message_data = getMessage();
$message = $message_data['message'] ?? '';
$message_type = $message_data['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="profil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- System Notification -->
    <?php if ($message): ?>
    <div class="system-notification <?php echo $message_type; ?>" id="systemNotification">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Header & Navigasi -->
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2><i class="fas fa-gamepad"></i> Game<span>TopUp</span></h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Beranda</a></li>
                    <li><a href="index.php#games" class="nav-link"><i class="fas fa-gamepad"></i> Game</a></li>
                    <li><a href="index.php#topup" class="nav-link"><i class="fas fa-shopping-cart"></i> Top Up</a></li>
                    <li><a href="index.php#history" class="nav-link"><i class="fas fa-history"></i> Riwayat</a></li>
                    <li>
                        <!-- User Dropdown Menu - UPDATED -->
                        <div class="user-dropdown">
                            <button class="user-menu-btn" id="headerProfilMenuBtn">
                                <div class="user-info">
                                    <div class="user-avatar"><?php echo strtoupper(substr($current_user['username'], 0, 1)); ?></div>
                                    <div class="user-name">
                                        <?php echo htmlspecialchars($current_user['username']); ?>
                                        <?php if ($current_user['is_admin']): ?>
                                        <span class="admin-badge">ADMIN</span>
                                        <?php endif; ?>
                                    </div>
                                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                                </div>
                            </button>
                            <div class="dropdown-content" id="headerProfilDropdown">
                                <a href="profil.php" class="active"><i class="fas fa-user-circle"></i> Profil</a>
                                <?php if ($current_user['is_admin']): ?>
                                <a href="admin.php" id="adminLink"><i class="fas fa-cog"></i> Admin Panel</a>
                                <?php endif; ?>
                                <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
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

    <!-- Profil Section -->
    <section class="profil-section">
        <div class="container">
            <!-- Judul dinamis berdasarkan role -->
            <h2 class="section-title">
                <?php echo $current_user['is_admin'] ? 'Profil Admin' : 'Profil User'; ?>
                <?php if ($current_user['is_admin']): ?>
                <span class="admin-badge-large">ADMIN</span>
                <?php endif; ?>
            </h2>
            
            <div class="profil-container">
                <!-- Sidebar Profil -->
                <div class="profil-sidebar">
                    <div class="profil-avatar">
                        <div class="avatar-icon <?php echo $current_user['is_admin'] ? 'admin-avatar' : ''; ?>">
                            <?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>
                        </div>
                        <h3><?php echo htmlspecialchars($current_user['username']); ?></h3>
                        <p><?php echo htmlspecialchars($current_user['email']); ?></p>
                        <div class="user-role <?php echo $current_user['is_admin'] ? 'admin-role' : ''; ?>">
                            <i class="fas fa-user-tag"></i> 
                            <?php echo $current_user['is_admin'] ? 'Administrator' : 'User'; ?>
                        </div>
                        <div class="profil-stats">
                            <div class="stat-item">
                                <i class="fas fa-history"></i>
                                <span>Total Transaksi: <strong><?php echo $total_transactions; ?></strong></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Member sejak: <strong><?php echo date('d/m/Y', strtotime($current_user['created_at'])); ?></strong></span>
                            </div>
                            <?php if ($current_user['is_admin']): ?>
                            <div class="stat-item admin-stat">
                                <i class="fas fa-crown"></i>
                                <span>Role: <strong>Administrator</strong></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="profil-menu">
                        <a href="?tab=edit-profil" class="profil-menu-btn <?php echo $active_tab === 'edit-profil' ? 'active' : ''; ?>">
                            <i class="fas fa-user-edit"></i> Edit Profil
                        </a>
                        <a href="?tab=ubah-password" class="profil-menu-btn <?php echo $active_tab === 'ubah-password' ? 'active' : ''; ?>">
                            <i class="fas fa-key"></i> Ubah Password
                        </a>
                        <a href="?tab=riwayat-transaksi" class="profil-menu-btn <?php echo $active_tab === 'riwayat-transaksi' ? 'active' : ''; ?>">
                            <i class="fas fa-receipt"></i> Riwayat Transaksi
                        </a>
                        <?php if ($current_user['is_admin']): ?>
                        <a href="admin.php" class="profil-menu-btn admin-btn">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Konten Profil -->
                <div class="profil-content">
                    <!-- Edit Profil Form -->
                    <div class="profil-tab <?php echo $active_tab === 'edit-profil' ? 'active' : ''; ?>" id="edit-profil">
                        <h3><i class="fas fa-user-edit"></i> Edit Profil</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="form-group">
                                <label for="editUsername"><i class="fas fa-user"></i> Username</label>
                                <input type="text" id="editUsername" name="username" required 
                                       value="<?php echo htmlspecialchars($current_user['username']); ?>">
                                <small class="form-help">Username harus unik dan 3-20 karakter</small>
                            </div>
                            <div class="form-group">
                                <label for="editEmail"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" id="editEmail" name="email" required 
                                       value="<?php echo htmlspecialchars($current_user['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-info-circle"></i> Informasi Akun</label>
                                <div class="account-info <?php echo $current_user['is_admin'] ? 'admin-info' : ''; ?>">
                                    <p><i class="fas fa-calendar"></i> Bergabung: 
                                        <span><?php echo date('d F Y', strtotime($current_user['created_at'])); ?></span>
                                    </p>
                                    <p><i class="fas fa-clock"></i> Terakhir login: 
                                        <span><?php echo $current_user['last_login'] ? date('d/m/Y H:i', strtotime($current_user['last_login'])) : 'Belum pernah login'; ?></span>
                                    </p>
                                    <p><i class="fas fa-user-tag"></i> Role: 
                                        <span><strong><?php echo $current_user['is_admin'] ? 'Administrator' : 'User'; ?></strong></span>
                                    </p>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                        </form>
                    </div>
                    
                    <!-- Ubah Password Form -->
                    <div class="profil-tab <?php echo $active_tab === 'ubah-password' ? 'active' : ''; ?>" id="ubah-password">
                        <h3><i class="fas fa-key"></i> Ubah Password</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="update_password" value="1">
                            <div class="form-group">
                                <label for="currentPassword"><i class="fas fa-lock"></i> Password Saat Ini</label>
                                <input type="password" id="currentPassword" name="current_password" required placeholder="Masukkan password saat ini">
                            </div>
                            <div class="form-group">
                                <label for="newPassword"><i class="fas fa-lock"></i> Password Baru</label>
                                <input type="password" id="newPassword" name="new_password" required placeholder="Masukkan password baru">
                                <small class="form-help">Minimal 8 karakter, kombinasi huruf dan angka</small>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword"><i class="fas fa-lock"></i> Konfirmasi Password Baru</label>
                                <input type="password" id="confirmPassword" name="confirm_password" required placeholder="Konfirmasi password baru">
                            </div>
                            <button type="submit" class="btn-primary"><i class="fas fa-sync-alt"></i> Ubah Password</button>
                        </form>
                    </div>
                    
                    <!-- Riwayat Transaksi -->
                    <div class="profil-tab <?php echo $active_tab === 'riwayat-transaksi' ? 'active' : ''; ?>" id="riwayat-transaksi">
                        <h3><i class="fas fa-receipt"></i> Riwayat Transaksi</h3>
                        <div class="transaksi-summary">
                            <div class="summary-card">
                                <i class="fas fa-shopping-cart"></i>
                                <h4>Total Transaksi</h4>
                                <p class="summary-count"><?php echo $total_transactions; ?></p>
                            </div>
                            <div class="summary-card">
                                <i class="fas fa-check-circle"></i>
                                <h4>Berhasil</h4>
                                <p class="summary-count success"><?php echo $success_transactions; ?></p>
                            </div>
                            <div class="summary-card">
                                <i class="fas fa-clock"></i>
                                <h4>Pending</h4>
                                <p class="summary-count pending"><?php echo $pending_transactions; ?></p>
                            </div>
                        </div>
                        
                        <div class="transaksi-table-container">
                            <table class="transaksi-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-calendar"></i> Tanggal</th>
                                        <th><i class="fas fa-gamepad"></i> Game</th>
                                        <th><i class="fas fa-box"></i> Paket</th>
                                        <th><i class="fas fa-money-bill-wave"></i> Harga</th>
                                        <th><i class="fas fa-info-circle"></i> Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($user_transactions)): ?>
                                    <tr>
                                        <td colspan="5" class="no-transactions">
                                            <p><i class="fas fa-receipt"></i></p>
                                            <p>Belum ada riwayat transaksi</p>
                                            <small>Silakan melakukan top up terlebih dahulu</small>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($user_transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($transaction['created_at'])); ?><br>
                                            <small><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                        </td>
                                        <td><?php echo getGameName($transaction['game_code']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['package_name']); ?></td>
                                        <td><?php echo formatPrice($transaction['price']); ?></td>
                                        <td><span class="status <?php echo $transaction['status']; ?>">
                                            <?php echo getStatusText($transaction['status']); ?>
                                        </span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
                        <li><a href="index.php#games"><i class="fas fa-mobile-alt"></i> Mobile Legends</a></li>
                        <li><a href="index.php#games"><i class="fas fa-cube"></i> Roblox</a></li>
                        <li><a href="index.php#games"><i class="fas fa-fire"></i> Free Fire</a></li>
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
                        <li><a href="profil.php" class="active"><i class="fas fa-user-circle"></i> Profil</a></li>
                        <li><a href="index.php#history"><i class="fas fa-history"></i> Riwayat</a></li>
                        <?php if ($current_user['is_admin']): ?>
                        <li><a href="admin.php"><i class="fas fa-cog"></i> Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> GameTopUp. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
            </div>
        </div>
    </footer>

    <!-- Script -->
    <script src="profil.js"></script>
</body>
</html>