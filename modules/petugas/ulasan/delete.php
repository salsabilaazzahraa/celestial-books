<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPetugas()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

setMessage('error', 'Anda tidak memiliki hak akses untuk menghapus ulasan! Silahkan hubungi admin.');
redirect('index.php');

// Berikut adalah kode yang seharusnya hanya diakses oleh admin
/*
$db = new Database();
$conn = $db->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage('error', 'ID ulasan tidak valid!');
    redirect('index.php');
}

$id = $_GET['id'];

try {
    // Hapus data ulasan berdasarkan id
    $query = "DELETE FROM ulasan_buku WHERE id = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$id]);
    
    if ($result) {
        setMessage('success', 'Ulasan berhasil dihapus!');
    } else {
        setMessage('error', 'Gagal menghapus ulasan!');
    }
} catch (PDOException $e) {
    setMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
}

redirect('index.php');
*/
?>