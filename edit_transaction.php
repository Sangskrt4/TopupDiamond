<?php
// File: edit_transaction.php
require_once 'config/database.php';
require_once 'config/helpers.php';

if (!isLoggedIn()) {
    showMessage('Silakan login terlebih dahulu!', 'error');
    redirect('index.php');
}

$database = new Database();
$conn = $database->getConnection();

$transaction_id = $_GET['id'] ?? 0;

// Get transaction
$query = "SELECT t.*, g.game_code, g.game_name, p.package_name 
          FROM transactions t
          JOIN games g ON t.game_id = g.id
          JOIN packages p ON t.package_id = p.id
          WHERE t.id = ? AND t.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $transaction_id, $_SESSION['user_id']);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    showMessage('Transaksi tidak ditemukan!', 'error');
    redirect('index.php');
}

if ($transaction['status'] !== 'pending') {
    showMessage('Transaksi sudah diproses, tidak bisa diedit!', 'error');
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $player_id = $_POST['player_id'] ?? '';
    $player_name = $_POST['player_name'] ?? '';
    
    $update_query = "UPDATE transactions SET player_id = ?, player_name = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $player_id, $player_name, $transaction_id);
    
    if ($update_stmt->execute()) {
        showMessage('Transaksi berhasil diperbarui!', 'success');
        redirect('index.php');
    } else {
        showMessage('Gagal memperbarui transaksi', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi - GameTopUp</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="max-width: 500px; margin: 100px auto; padding: 20px;">
        <h2>Edit Transaksi</h2>
        <form method="POST">
            <div class="form-group">
                <label>ID Player</label>
                <input type="text" name="player_id" value="<?php echo htmlspecialchars($transaction['player_id']); ?>" required>
            </div>
            <div class="form-group">
                <label>Nama Player</label>
                <input type="text" name="player_name" value="<?php echo htmlspecialchars($transaction['player_name']); ?>" required>
            </div>
            <button type="submit" class="btn-primary">Simpan</button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>