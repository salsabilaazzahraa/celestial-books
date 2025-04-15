<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPeminjam()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMessage('error', 'Metode tidak valid!');
    redirect('/modules/peminjam/ulasan/index.php');
}

// Validasi input
$ulasan_id = isset($_POST['ulasan_id']) ? intval($_POST['ulasan_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$ulasan = isset($_POST['ulasan']) ? trim($_POST['ulasan']) : '';

// Validasi data
if ($ulasan_id === 0) {
    setMessage('error', 'Parameter tidak valid!');
    redirect('/modules/peminjam/ulasan/index.php');
}

if ($rating < 1 || $rating > 5) {
    setMessage('error', 'Rating harus antara 1-5!');
    redirect('/modules/peminjam/ulasan/edit_ulasan.php?id=' . $ulasan_id);
}

if (strlen($ulasan) < 10 || strlen($ulasan) > 500) {
    setMessage('error', 'Ulasan harus antara 10-500 karakter!');
    redirect('/modules/peminjam/ulasan/edit_ulasan.php?id=' . $ulasan_id);
}

$db = new Database();
$conn = $db->getConnection();

// Validasi kepemilikan ulasan
$query = "SELECT id, buku_id, status FROM ulasan_buku WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$ulasan_id, $_SESSION['user_id']]);
$data_ulasan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data_ulasan) {
    setMessage('error', 'Data ulasan tidak ditemukan atau tidak valid!');
    redirect('/modules/peminjam/ulasan/index.php');
}

// Hanya ulasan dengan status pending atau rejected yang bisa diedit
if ($data_ulasan['status'] !== 'pending' && $data_ulasan['status'] !== 'rejected') {
    setMessage('warning', 'Ulasan yang sudah disetujui tidak dapat diedit!');
    redirect('/modules/peminjam/ulasan/index.php');
}

// Update ulasan
try {
    $query = "
        UPDATE ulasan_buku SET 
        rating = ?, 
        ulasan = ?, 
        status = 'pending',
        updated_at = NOW() 
        WHERE id = ? AND user_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$rating, $ulasan, $ulasan_id, $_SESSION['user_id']]);
    
    // Update rating di tabel buku
    $query = "
        UPDATE buku SET 
        rating = (SELECT ROUND(AVG(rating), 1) FROM ulasan_buku WHERE buku_id = ? AND status = 'approved'),
        updated_at = NOW() 
        WHERE id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$data_ulasan['buku_id'], $data_ulasan['buku_id']]);
    
    setMessage('success', 'Ulasan berhasil diperbarui dan sedang menunggu persetujuan!');
    redirect('/modules/peminjam/ulasan/index.php');
} catch (PDOException $e) {
    setMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
    redirect('/modules/peminjam/ulasan/edit_ulasan.php?id=' . $ulasan_id);
}