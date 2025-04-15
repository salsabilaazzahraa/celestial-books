<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPeminjam()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

// Validasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMessage('error', 'Metode tidak diizinkan!');
    redirect('/modules/peminjam/ulasan/index.php');
}

// Validasi data
if (!isset($_POST['ulasan_id']) || !isset($_POST['rating']) || !isset($_POST['ulasan'])) {
    setMessage('error', 'Data tidak lengkap!');
    redirect('/modules/peminjam/ulasan/index.php');
}

$ulasan_id = $_POST['ulasan_id'];
$rating = intval($_POST['rating']);
$ulasan = trim($_POST['ulasan']);

// Validasi rating
if ($rating < 1 || $rating > 5) {
    setMessage('error', 'Rating harus antara 1-5!');
    redirect('/modules/peminjam/ulasan/edit_ulasan.php?id=' . $ulasan_id);
}

// Validasi panjang ulasan
if (strlen($ulasan) < 10 || strlen($ulasan) > 500) {
    setMessage('error', 'Ulasan harus antara 10-500 karakter!');
    redirect('/modules/peminjam/ulasan/edit_ulasan.php?id=' . $ulasan_id);
}

$db = new Database();
$conn = $db->getConnection();

// Validasi kepemilikan ulasan dan status
$query = "
    SELECT id 
    FROM ulasan_buku 
    WHERE id = ? AND user_id = ? AND status = 'pending'
";
$stmt = $conn->prepare($query);
$stmt->execute([$ulasan_id, $_SESSION['user_id']]);

if ($stmt->rowCount() === 0) {
    setMessage('error', 'Data ulasan tidak valid atau tidak dapat diubah!');
    redirect('/modules/peminjam/ulasan/index.php');
}

try {
    // Update ulasan
    $query = "
        UPDATE ulasan_buku 
        SET rating = ?, ulasan = ?, updated_at = NOW()
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$rating, $ulasan, $ulasan_id, $_SESSION['user_id']]);
    
    setMessage('success', 'Ulasan berhasil diperbarui dan menunggu persetujuan admin!');
} catch (PDOException $e) {
    setMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
}

redirect('/modules/peminjam/ulasan/index.php');