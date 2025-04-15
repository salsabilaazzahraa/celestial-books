<?php
require_once '../../../config/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    setMessage('danger', 'Anda tidak memiliki akses ke halaman ini!');
    redirect('/');
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Cek apakah petugas memiliki transaksi aktif
    $check_query = "SELECT COUNT(*) FROM peminjaman WHERE petugas_id = ? AND status_peminjaman = 'dipinjam'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$id]);
    
    if ($check_stmt->fetchColumn() > 0) {
        setMessage('danger', 'Petugas tidak dapat dihapus karena masih memiliki transaksi aktif!');
    } else {
        // Cek apakah petugas valid
        $check_petugas = "SELECT * FROM users WHERE id = ? AND role = 'petugas'";
        $stmt_petugas = $conn->prepare($check_petugas);
        $stmt_petugas->execute([$id]);
        
        if ($stmt_petugas->rowCount() === 0) {
            setMessage('danger', 'Petugas tidak ditemukan!');
        } else {
            $query = "DELETE FROM users WHERE id = ? AND role = 'petugas'";
            $stmt = $conn->prepare($query);
            
            if ($stmt->execute([$id])) {
                setMessage('success', 'Petugas berhasil dihapus!');
            } else {
                setMessage('danger', 'Gagal menghapus petugas!');
            }
        }
    }
} else {
    setMessage('danger', 'ID Petugas tidak valid!');
}

redirect('/modules/admin/petugas/index.php');
