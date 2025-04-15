<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPetugas()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

// Validasi parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setMessage('error', 'Parameter tidak valid!');
    redirect('/modules/petugas/ulasan/index.php');
}

$ulasan_id = intval($_GET['id']);

$db = new Database();
$conn = $db->getConnection();

// Ambil data ulasan
$query = "SELECT id, buku_id FROM ulasan_buku WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$ulasan_id]);
$data_ulasan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data_ulasan) {
    setMessage('error', 'Data ulasan tidak ditemukan!');
    redirect('/modules/petugas/ulasan/index.php');
}

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Update status ulasan
    $query = "
        UPDATE ulasan_buku SET 
        status = 'rejected',
        updated_at = NOW() 
        WHERE id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$ulasan_id]);
    
    // Update rating di tabel buku
    $query = "
        UPDATE buku SET 
        rating = (SELECT COALESCE(ROUND(AVG(rating), 1), 0) FROM ulasan_buku WHERE buku_id = ? AND status = 'approved'),
        updated_at = NOW() 
        WHERE id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$data_ulasan['buku_id'], $data_ulasan['buku_id']]);
    
    // Commit transaction
    $conn->commit();
    
    setMessage('success', 'Ulasan berhasil ditolak!');
} catch (PDOException $e) {
    // Rollback transaction
    $conn->rollBack();
    setMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
}

redirect('/modules/petugas/ulasan/index.php');