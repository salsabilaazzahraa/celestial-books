<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isAdmin()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

// Validasi parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setMessage('error', 'Parameter tidak valid!');
    redirect('/modules/admin/ulasan/index.php');
}

$ulasan_id = intval($_GET['id']);

$db = new Database();
$conn = $db->getConnection();

// Periksa apakah kolom rating ada di tabel ulasan_buku
$checkColumn = $conn->query("SHOW COLUMNS FROM ulasan_buku LIKE 'rating'");
$columnExists = $checkColumn->fetch();

if (!$columnExists) {
    // Tambahkan kolom rating jika belum ada
    $conn->exec("ALTER TABLE ulasan_buku ADD COLUMN rating INT NOT NULL DEFAULT 0");
    setMessage('info', 'Struktur database telah diperbarui.');
}

// Ambil data ulasan
$query = "SELECT id, buku_id, rating FROM ulasan_buku WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$ulasan_id]);
$data_ulasan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data_ulasan) {
    setMessage('error', 'Data ulasan tidak ditemukan!');
    redirect('/modules/admin/ulasan/index.php');
}

try {
    // Periksa apakah transaksi bisa dimulai
    if ($conn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        // Begin transaction
        $conn->beginTransaction();
        $transaction_started = true;
    } else {
        $transaction_started = false;
    }
    
    // Update status ulasan
    $query = "
        UPDATE ulasan_buku SET 
        status = 'approved',
        updated_at = NOW() 
        WHERE id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$ulasan_id]);
    
    // Periksa kolom rating di tabel buku
    $checkRatingColumn = $conn->query("SHOW COLUMNS FROM buku LIKE 'rating'");
    $ratingColumnExists = $checkRatingColumn->fetch();
    
    if (!$ratingColumnExists) {
        // Tambahkan kolom rating jika belum ada
        $conn->exec("ALTER TABLE buku ADD COLUMN rating DECIMAL(3,1) NOT NULL DEFAULT 0");
    }
    
    // Update rating di tabel buku
    $query = "
        UPDATE buku SET 
        rating = (SELECT COALESCE(ROUND(AVG(rating), 1), 0) FROM ulasan_buku WHERE buku_id = ? AND status = 'approved'),
        updated_at = NOW() 
        WHERE id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$data_ulasan['buku_id'], $data_ulasan['buku_id']]);
    
    // Commit transaction jika dimulai
    if ($transaction_started) {
        $conn->commit();
    }
    
    setMessage('success', 'Ulasan berhasil disetujui!');
} catch (PDOException $e) {
    // Rollback transaction jika dimulai
    if (isset($transaction_started) && $transaction_started) {
        $conn->rollBack();
    }
    setMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
}

redirect('/modules/admin/ulasan/index.php');
?>