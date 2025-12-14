<?php
// File: config/helpers.php

// Start session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Format harga menjadi Rupiah
function formatPrice($price) {
    if (empty($price)) return 'Rp 0';
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// Dapatkan nama game dari kode game
function getGameName($gameCode) {
    $games = [
        'ml' => 'Mobile Legends',
        'roblox' => 'Roblox',
        'freefire' => 'Free Fire'
    ];
    return isset($games[$gameCode]) ? $games[$gameCode] : $gameCode;
}

// Dapatkan teks status dari kode status
function getStatusText($status) {
    $statusText = [
        'pending' => 'Menunggu',
        'success' => 'Berhasil',
        'failed' => 'Gagal'
    ];
    return isset($statusText[$status]) ? $statusText[$status] : $status;
}

// Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Cek apakah user adalah admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true;
}

// Redirect ke URL tertentu
function redirect($url) {
    header("Location: $url");
    exit();
}

// Tampilkan pesan (disimpan di session)
function showMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// Dapatkan pesan yang disimpan
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Fungsi untuk sensor ID Player (untuk user biasa)
function maskPlayerId($playerId, $isAdmin = false) {
    if ($isAdmin) {
        // Admin bisa lihat lengkap
        return $playerId;
    }
    
    // User biasa: sensor 4 karakter terakhir
    if (strlen($playerId) > 4) {
        return substr($playerId, 0, 4) . '****';
    } else {
        return '****';
    }
}

// Fungsi untuk sanitasi input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk validasi email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Fungsi untuk validasi username
function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

// Fungsi untuk validasi password
function validatePassword($password) {
    return strlen($password) >= 6;
}

// Fungsi untuk menampilkan badge status
function getStatusBadge($status) {
    $classes = [
        'pending' => 'pending',
        'success' => 'success',
        'failed' => 'failed'
    ];
    
    $class = isset($classes[$status]) ? $classes[$status] : 'pending';
    $text = getStatusText($status);
    
    return "<span class='status $class'>$text</span>";
}

// Fungsi untuk menampilkan badge admin
function getAdminBadge() {
    return '<span class="admin-badge">ADMIN</span>';
}

// Fungsi untuk menghitung waktu yang lalu
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "$minutes menit yang lalu";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "$hours jam yang lalu";
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return "$days hari yang lalu";
    } else {
        return date('d M Y', $time);
    }
}

// Fungsi untuk mendapatkan avatar user
function getUserAvatar($username, $size = 32) {
    $initial = strtoupper(substr($username, 0, 1));
    $avatarColor = '#' . substr(md5($username), 0, 6);
    
    return [
        'initial' => $initial,
        'color' => $avatarColor,
        'size' => $size
    ];
}

// Fungsi untuk mendapatkan class CSS berdasarkan role
function getUserRoleClass($isAdmin) {
    return $isAdmin ? 'admin-role' : 'user-role';
}

// Fungsi untuk mendapatkan teks role
function getUserRoleText($isAdmin) {
    return $isAdmin ? 'Administrator' : 'User';
}

// Fungsi untuk memformat tanggal
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

// Fungsi untuk memformat tanggal dengan waktu
function formatDateTime($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}
?>