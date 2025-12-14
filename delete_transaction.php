<?php
// File: delete_transaction.php
require_once 'config/database.php';
require_once 'config/helpers.php';

if (!isLoggedIn()) {
    showMessage('Silakan login terlebih dahulu!', 'error');
    redirect('index.php');
}

$database = new Database();
$conn = $database->getConnection();

$transaction_id = $_GET['id'] ?? 0;

// Get transaction to verify ownership
$query = "SELECT * FROM transactions WHERE id = ? AND user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $transaction_id, $_SESSION['user_id']);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    showMessage('Transaksi tidak ditemukan atau tidak bisa dihapus!', 'error');
    redirect('index.php');
}

// Delete transaction
$delete_query = "DELETE FROM transactions WHERE id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("i", $transaction_id);

if ($delete_stmt->execute()) {
    showMessage('Transaksi berhasil dihapus!', 'success');
} else {
    showMessage('Gagal menghapus transaksi', 'error');
}

redirect('index.php');
?>