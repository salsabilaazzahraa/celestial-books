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
if (!isset($_POST['peminjaman_id']) || !isset($_POST['buku_id']) || !isset($_POST['rating']) || !isset($_POST['ulasan'])) {
    setMessage('error', 'Data tidak lengkap!');
    redirect('/modules/peminjam/ulasan/index.php');
}

$peminjaman_id = $_POST['peminjaman_id'];
$buku_id = $_POST['buku_id'];
$rating = intval($_POST['rating']);
$ulasan = trim($_POST['ulasan']);

// Validasi rating
if ($rating < 1 || $rating > 5) {
    setMessage('error', 'Rating harus antara 1-5!');
    redirect('/modules/peminjam/ulasan/form_ulasan.php?peminjaman_id=' . $peminjaman_id . '&buku_id=' . $buku_id);
}

// Validasi panjang ulasan
if (strlen($ulasan) < 10 || strlen($ulasan) > 500) {
    setMessage('error', 'Ulasan harus antara 10-500 karakter!');
    redirect('/modules/peminjam/ulasan/form_ulasan.php?peminjaman_id=' . $peminjaman_id . '&buku_id=' . $buku_id);
}

$db = new Database();
$conn = $db->getConnection();

// Validasi peminjaman
$query = "
    SELECT id 
    FROM peminjaman 
    WHERE id = ? AND user_id = ? AND buku_id = ? AND status_peminjaman = 'dikembalikan'
";
$stmt = $conn->prepare($query);
$stmt->execute([$peminjaman_id, $_SESSION['user_id'], $buku_id]);

if ($stmt->rowCount() === 0) {
    setMessage('error', 'Data peminjaman tidak valid!');
    redirect('/modules/peminjam/ulasan/index.php');
}

// Cek apakah sudah pernah memberikan ulasan
$query = "SELECT id FROM ulasan_buku WHERE user_id = ? AND buku_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id'], $buku_id]);

if ($stmt->rowCount() > 0) {
    setMessage('warning', 'Anda sudah memberikan ulasan untuk buku ini!');
    redirect('/modules/peminjam/ulasan/index.php');
}

try {
    // Simpan ulasan (menghapus peminjaman_id dari daftar kolom)
    $query = "
        INSERT INTO ulasan_buku (user_id, buku_id, rating, ulasan, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $buku_id, $rating, $ulasan]);
    
    setMessage('success', 'Ulasan berhasil dikirim dan menunggu persetujuan admin!');
} catch (PDOException $e) {
    setMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
}

redirect('/modules/peminjam/ulasan/index.php');