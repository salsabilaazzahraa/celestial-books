<!-- File: modules/admin/users/delete.php -->
<?php
require_once '../../../config/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('/');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Cek apakah user yang akan dihapus bukan user yang sedang login
if ($id === $_SESSION['user_id']) {
    setMessage('danger', 'Tidak dapat menghapus akun yang sedang digunakan!');
    redirect('/modules/admin/users/index.php');
}

$database = new Database();
$conn = $database->getConnection();

// Cek apakah user memiliki peminjaman aktif
$check_query = "SELECT COUNT(*) FROM peminjaman WHERE user_id = ? AND status_peminjaman = 'dipinjam'";
$check_stmt = $conn->prepare($check_query);
$check_stmt->execute([$id]);

if ($check_stmt->fetchColumn() > 0) {
    setMessage('danger', 'Tidak dapat menghapus pengguna yang masih memiliki peminjaman aktif!');
    redirect('/modules/admin/users/index.php');
}

// Hapus user
$query = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($query);

if ($stmt->execute([$id])) {
    setMessage('success', 'Pengguna berhasil dihapus!');
} else {
    setMessage('danger', 'Gagal menghapus pengguna!');
}

redirect('/modules/admin/users/index.php');
?>
