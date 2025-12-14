<?php
// File: admin.php
require_once 'config/database.php';
require_once 'config/helpers.php';

// Cek apakah user adalah admin
if (!isLoggedIn() || !isAdmin()) {
    showMessage('Akses ditolak! Hanya admin yang bisa mengakses halaman ini.', 'error');
    redirect('index.php');
}

$database = new Database();
$conn = $database->getConnection();

// Get admin data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update transaction status
    if (isset($_POST['update_status'])) {
        $transaction_id = sanitizeInput($_POST['transaction_id'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? 'pending');
        $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
        
        $update_query = "UPDATE transactions SET status = ?, admin_notes = ?, 
                         processed_at = NOW(), processed_by = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssi", $status, $admin_notes, $current_user['username'], $transaction_id);
        
        if ($update_stmt->execute()) {
            showMessage('Status transaksi berhasil diperbarui!', 'success');
        } else {
            showMessage('Gagal memperbarui status transaksi', 'error');
        }
        redirect('admin.php?tab=transactions');
    }
    
    // Delete transaction
    if (isset($_POST['delete_transaction'])) {
        $transaction_id = sanitizeInput($_POST['transaction_id'] ?? 0);
        
        $delete_query = "DELETE FROM transactions WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $transaction_id);
        
        if ($delete_stmt->execute()) {
            showMessage('Transaksi berhasil dihapus!', 'success');
        } else {
            showMessage('Gagal menghapus transaksi', 'error');
        }
        redirect('admin.php?tab=transactions');
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id_to_delete = sanitizeInput($_POST['user_id'] ?? 0);
        
        // Tidak bisa hapus diri sendiri atau admin
        if ($user_id_to_delete != $_SESSION['user_id']) {
            $delete_query = "DELETE FROM users WHERE id = ? AND is_admin = FALSE";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $user_id_to_delete);
            
            if ($delete_stmt->execute()) {
                // Also delete user's transactions
                $delete_transactions_query = "DELETE FROM transactions WHERE user_id = ?";
                $delete_transactions_stmt = $conn->prepare($delete_transactions_query);
                $delete_transactions_stmt->bind_param("i", $user_id_to_delete);
                $delete_transactions_stmt->execute();
                
                showMessage('User berhasil dihapus!', 'success');
            } else {
                showMessage('Gagal menghapus user', 'error');
            }
        } else {
            showMessage('Tidak bisa menghapus akun sendiri!', 'error');
        }
        redirect('admin.php?tab=users');
    }
}

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(price) as total_revenue
               FROM transactions";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get recent transactions (last 5)
$recent_query = "SELECT t.*, u.username, g.game_name, p.package_name 
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                JOIN games g ON t.game_id = g.id
                JOIN packages p ON t.package_id = p.id
                ORDER BY t.created_at DESC 
                LIMIT 5";
$recent_result = $conn->query($recent_query);
$recent_transactions = [];

while ($row = $recent_result->fetch_assoc()) {
    $recent_transactions[] = $row;
}

// Get all transactions with filters
$filter_status = $_GET['status'] ?? 'all';
$filter_game = $_GET['game'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$all_transactions_query = "SELECT t.*, u.username, g.game_code, g.game_name, p.package_name 
                          FROM transactions t
                          JOIN users u ON t.user_id = u.id
                          JOIN games g ON t.game_id = g.id
                          JOIN packages p ON t.package_id = p.id
                          WHERE 1=1";

$params = [];
$types = "";

if ($filter_status != 'all') {
    $all_transactions_query .= " AND t.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_game != 'all') {
    $all_transactions_query .= " AND g.game_code = ?";
    $params[] = $filter_game;
    $types .= "s";
}

if (!empty($search_query)) {
    $all_transactions_query .= " AND (u.username LIKE ? OR t.player_id LIKE ? OR t.player_name LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$all_transactions_query .= " ORDER BY t.created_at DESC";

$all_transactions_stmt = $conn->prepare($all_transactions_query);

if (!empty($params)) {
    $all_transactions_stmt->bind_param($types, ...$params);
}

$all_transactions_stmt->execute();
$all_transactions_result = $all_transactions_stmt->get_result();
$all_transactions = [];

while ($row = $all_transactions_result->fetch_assoc()) {
    $all_transactions[] = $row;
}

// Get all users
$users_query = "SELECT u.*, 
               (SELECT COUNT(*) FROM transactions t WHERE t.user_id = u.id) as transaction_count,
               (SELECT SUM(price) FROM transactions t WHERE t.user_id = u.id AND t.status = 'success') as total_spent
               FROM users u
               ORDER BY u.created_at DESC";
$users_result = $conn->query($users_query);
$all_users = [];

while ($row = $users_result->fetch_assoc()) {
    $all_users[] = $row;
}

// Get all games for filter
$games_query = "SELECT * FROM games WHERE is_active = TRUE";
$games_result = $conn->query($games_query);
$games = [];

while ($row = $games_result->fetch_assoc()) {
    $games[] = $row;
}

// Get message
$message_data = getMessage();
$message = $message_data['message'] ?? '';
$message_type = $message_data['type'] ?? '';

// Get active tab
$active_tab = $_GET['tab'] ?? 'dashboard';

// Get total users count
$total_users_query = "SELECT COUNT(*) as total FROM users WHERE is_admin = FALSE";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - GameTopUp</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
    </style>
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
                    <h2>
                        <i class="fas fa-gamepad"></i> 
                        Game<span>TopUp</span>
                        <span class="admin-badge">Admin Panel</span>
                    </h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Beranda</a></li>
                    <li><a href="admin.php?tab=dashboard" class="nav-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li><a href="admin.php?tab=transactions" class="nav-link <?php echo $active_tab === 'transactions' ? 'active' : ''; ?>">
                        <i class="fas fa-exchange-alt"></i> Transaksi</a>
                    </li>
                    <li><a href="admin.php?tab=users" class="nav-link <?php echo $active_tab === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Pengguna</a>
                    </li>
                    <li>
                        <div class="user-dropdown">
                            <button class="user-menu-btn" id="adminMenuBtn">
                                <div class="user-info">
                                    <div class="user-avatar"><?php echo strtoupper(substr($current_user['username'], 0, 1)); ?></div>
                                    <div class="user-name"><?php echo htmlspecialchars($current_user['username']); ?></div>
                                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                                </div>
                            </button>
                            <div class="dropdown-content" id="adminDropdown">
                                <a href="profil.php"><i class="fas fa-user-circle"></i> Profil</a>
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

    <!-- Admin Dashboard -->
    <section class="admin-section">
        <div class="container">
            <?php if ($active_tab === 'dashboard'): ?>
            <!-- Dashboard Content -->
            <h2 class="section-title">Admin Dashboard</h2>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Transaksi</h3>
                        <p class="stat-number"><?php echo $stats['total'] ?? 0; ?></p>
                        <p class="stat-desc">Semua transaksi</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Pendapatan</h3>
                        <p class="stat-number"><?php echo formatPrice($stats['total_revenue'] ?? 0); ?></p>
                        <p class="stat-desc">Dari transaksi berhasil</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total User</h3>
                        <p class="stat-number"><?php echo $total_users; ?></p>
                        <p class="stat-desc">User terdaftar</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Pending</h3>
                        <p class="stat-number"><?php echo $stats['pending'] ?? 0; ?></p>
                        <p class="stat-desc">Menunggu konfirmasi</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="recent-transactions">
                <h3><i class="fas fa-history"></i> Transaksi Terbaru</h3>
                <div class="transactions-table-container">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar"></i> Tanggal</th>
                                <th><i class="fas fa-user"></i> User</th>
                                <th><i class="fas fa-gamepad"></i> Game</th>
                                <th><i class="fas fa-box"></i> Paket</th>
                                <th><i class="fas fa-money-bill-wave"></i> Harga</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-cog"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_transactions)): ?>
                            <tr>
                                <td colspan="7" class="no-data">
                                    <p><i class="fas fa-exchange-alt"></i></p>
                                    <p>Belum ada transaksi</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($transaction['created_at'])); ?><br>
                                    <small><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['game_name']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['package_name']); ?></td>
                                <td><?php echo formatPrice($transaction['price']); ?></td>
                                <td><span class="status-badge <?php echo $transaction['status']; ?>">
                                    <?php echo getStatusText($transaction['status']); ?>
                                </span></td>
                                <td>
                                    <div class="admin-actions">
                                        <button class="action-btn view-btn" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $transaction['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($active_tab === 'transactions'): ?>
            <!-- Transactions Management -->
            <h2 class="section-title">Manajemen Transaksi</h2>
            
            <!-- Quick Filter Buttons -->
            <div class="quick-filter-buttons">
                <a href="admin.php?tab=transactions&status=all" class="quick-filter-btn all <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> Semua
                </a>
                <a href="admin.php?tab=transactions&status=pending" class="quick-filter-btn pending <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                </a>
                <a href="admin.php?tab=transactions&status=success" class="quick-filter-btn success <?php echo $filter_status === 'success' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Berhasil
                </a>
                <a href="admin.php?tab=transactions&status=failed" class="quick-filter-btn failed <?php echo $filter_status === 'failed' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Gagal
                </a>
            </div>
            
            <!-- Advanced Filters -->
            <div class="admin-filters">
                <div class="filter-group">
                    <label for="statusFilter"><i class="fas fa-filter"></i> Filter Status</label>
                    <select id="statusFilter" onchange="filterTransactions()">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="success" <?php echo $filter_status === 'success' ? 'selected' : ''; ?>>Berhasil</option>
                        <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Gagal</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="gameFilter"><i class="fas fa-gamepad"></i> Filter Game</label>
                    <select id="gameFilter" onchange="filterTransactions()">
                        <option value="all" <?php echo $filter_game === 'all' ? 'selected' : ''; ?>>Semua Game</option>
                        <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_code']; ?>" <?php echo $filter_game === $game['game_code'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($game['game_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="searchInput"><i class="fas fa-search"></i> Pencarian</label>
                    <input type="text" id="searchInput" placeholder="Cari user/ID player..." 
                           value="<?php echo htmlspecialchars($search_query); ?>" 
                           onkeyup="searchTransactions(event)">
                </div>
                <button class="btn-secondary" onclick="resetFilters()"><i class="fas fa-redo"></i> Reset Filter</button>
            </div>
            
            <!-- Transactions Table -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-calendar"></i> Tanggal & Waktu</th>
                            <th><i class="fas fa-user"></i> User</th>
                            <th><i class="fas fa-gamepad"></i> Game</th>
                            <th><i class="fas fa-box"></i> Paket</th>
                            <th><i class="fas fa-id-card"></i> ID Player</th>
                            <th><i class="fas fa-money-bill-wave"></i> Harga</th>
                            <th><i class="fas fa-info-circle"></i> Status</th>
                            <th><i class="fas fa-cog"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_transactions)): ?>
                        <tr>
                            <td colspan="9" class="no-data">
                                <p><i class="fas fa-exchange-alt"></i></p>
                                <p>Tidak ada transaksi ditemukan</p>
                                <?php if ($filter_status !== 'all' || $filter_game !== 'all' || !empty($search_query)): ?>
                                <p><small>Coba ubah filter atau kata kunci pencarian</small></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($all_transactions as $transaction): ?>
                        <tr>
                            <td>#<?php echo $transaction['id']; ?></td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($transaction['created_at'])); ?><br>
                                <small><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                            <td><?php echo getGameName($transaction['game_code']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['package_name']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['player_id']); ?></td>
                            <td><?php echo formatPrice($transaction['price']); ?></td>
                            <td><span class="status-badge <?php echo $transaction['status']; ?>">
                                <?php echo getStatusText($transaction['status']); ?>
                            </span></td>
                            <td>
                                <div class="admin-actions">
                                    <button class="action-btn view-btn" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $transaction['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($transaction['status'] === 'pending'): ?>
                                    <button class="action-btn success-btn" onclick="approveTransaction(<?php echo $transaction['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="action-btn failed-btn" onclick="rejectTransaction(<?php echo $transaction['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="action-btn delete-btn" onclick="deleteTransactionAdmin(<?php echo $transaction['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="admin-pagination">
                <button class="btn-secondary" disabled><i class="fas fa-chevron-left"></i> Sebelumnya</button>
                <span class="page-info">Halaman 1 dari 1</span>
                <button class="btn-secondary" disabled>Selanjutnya <i class="fas fa-chevron-right"></i></button>
            </div>
            <?php endif; ?>
            
            <?php if ($active_tab === 'users'): ?>
            <!-- Users Management -->
            <h2 class="section-title">Manajemen Pengguna</h2>
            
            <!-- Users Stats -->
            <div class="users-stats">
                <div class="stat-card small">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total User</h3>
                        <p class="stat-number"><?php echo $total_users; ?></p>
                    </div>
                </div>
                <div class="stat-card small">
                    <div class="stat-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Aktif</h3>
                        <p class="stat-number"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Username</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-calendar"></i> Bergabung</th>
                            <th><i class="fas fa-history"></i> Total Transaksi</th>
                            <th><i class="fas fa-money-bill-wave"></i> Total Belanja</th>
                            <th><i class="fas fa-clock"></i> Terakhir Login</th>
                            <th><i class="fas fa-cog"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_users)): ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <p><i class="fas fa-users"></i></p>
                                <p>Tidak ada pengguna</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($user['username']); ?>
                                <?php if ($user['is_admin']): ?>
                                <span class="admin-badge" style="margin-left: 5px; font-size: 0.7rem; padding: 2px 6px;">ADMIN</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td><?php echo $user['transaction_count']; ?></td>
                            <td><?php echo formatPrice($user['total_spent'] ?? 0); ?></td>
                            <td><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-'; ?></td>
                            <td>
                                <div class="admin-actions">
                                    <button class="action-btn view-btn" onclick="viewUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (!$user['is_admin'] && $user['id'] != $_SESSION['user_id']): ?>
                                    <button class="action-btn delete-btn" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="admin-pagination">
                <button class="btn-secondary" disabled><i class="fas fa-chevron-left"></i> Sebelumnya</button>
                <span class="page-info">Halaman 1 dari 1</span>
                <button class="btn-secondary" disabled>Selanjutnya <i class="fas fa-chevron-right"></i></button>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal Edit Transaction -->
    <div id="editTransactionModal" class="modal"></div>

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
                        <li><a href="index.php"><i class="fas fa-home"></i> Kembali ke Beranda</a></li>
                        <li><a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> GameTopUp. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
            </div>
        </div>
    </footer>

    <!-- Script -->
    <script src="admin.js"></script>
</body>
</html>