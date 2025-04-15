<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPeminjam()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

// Validasi parameter
if (!isset($_GET['id'])) {
    setMessage('error', 'Parameter tidak valid!');
    redirect('/modules/peminjam/ulasan/index.php');
}

$ulasan_id = $_GET['id'];

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
    setMessage('error', 'Data ulasan tidak valid atau tidak dapat dihapus!');
    redirect('/modules/peminjam/ulasan/index.php');
}

try {
    // Hapus ulasan
    $query = "DELETE FROM ulasan_buku WHERE id = ? AND user_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$ulasan_id, $_SESSION['user_id']]);
    
    setMessage('success', 'Ulasan berhasil dihapus!');
} catch (PDOException $e) {
    setMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
}

redirect('/modules/peminjam/ulasan/index.php');