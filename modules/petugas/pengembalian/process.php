<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPetugas()) {
    setMessage('error', 'Akses ditolak! Anda bukan petugas.');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Pastikan ID peminjaman ada
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    setMessage('error', 'ID peminjaman tidak valid.');
    redirect('/modules/petugas/peminjaman/index.php');
}

try {
    // Ambil data peminjaman
    $loan_query = "
        SELECT p.*, b.id as buku_id, b.judul, b.stok
        FROM peminjaman p 
        JOIN buku b ON p.buku_id = b.id 
        WHERE p.id = :id
    ";
    $loan_stmt = $conn->prepare($loan_query);
    $loan_stmt->execute(['id' => $id]);
    $loan = $loan_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        throw new Exception("Data peminjaman tidak ditemukan.");
    }
    
    // Cek status peminjaman
    if ($loan['status_peminjaman'] !== 'dipinjam') {
        throw new Exception("Peminjaman ini sudah dikembalikan.");
    }
    
    // Hitung keterlambatan dan denda
    $tgl_pinjam = strtotime($loan['tanggal_peminjaman']);
    $tgl_batas = strtotime('+7 days', $tgl_pinjam);
    $tgl_kembali = time();
    
    $keterlambatan = $tgl_kembali > $tgl_batas ? ceil(($tgl_kembali - $tgl_batas) / (60 * 60 * 24)) : 0;
    $denda = $keterlambatan * 1000; // Rp 1.000 per hari keterlambatan
    
    // Mulai transaksi
    $conn->beginTransaction();
    
    // Update status peminjaman
    $update_query = "
        UPDATE peminjaman 
        SET status_peminjaman = 'dikembalikan', 
            tanggal_dikembalikan = :tanggal_dikembalikan, 
            denda = :denda
        WHERE id = :id
    ";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->execute([
        'tanggal_dikembalikan' => date('Y-m-d'),
        'denda' => $denda,
        'id' => $id
    ]);
    
    // Tambah stok buku
    $update_buku_query = "UPDATE buku SET stok = stok + 1 WHERE id = :id";
    $update_buku_stmt = $conn->prepare($update_buku_query);
    $update_buku_stmt->execute(['id' => $loan['buku_id']]);
    
    // Tambah riwayat pengembalian
    $catatan = "Buku dikembalikan oleh petugas";
    if ($keterlambatan > 0) {
        $catatan .= " dengan keterlambatan {$keterlambatan} hari dan denda Rp " . number_format($denda, 0, ',', '.');
    } else {
        $catatan .= " tepat waktu";
    }
    
    $riwayat_query = "
        INSERT INTO riwayat_peminjaman (peminjaman_id, status_perubahan, catatan, changed_by)
        VALUES (:peminjaman_id, 'dikembalikan', :catatan, :changed_by)
    ";
    $riwayat_stmt = $conn->prepare($riwayat_query);
    $riwayat_stmt->execute([
        'peminjaman_id' => $id,
        'catatan' => $catatan,
        'changed_by' => $_SESSION['user_id']
    ]);
    
    // Commit transaksi
    $conn->commit();
    
    setMessage('success', 'Buku berhasil dikembalikan.' . ($keterlambatan > 0 ? ' Denda: Rp ' . number_format($denda, 0, ',', '.') : ''));
    redirect('/modules/petugas/peminjaman/detail.php?id=' . $id);
    
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollBack();
    
    setMessage('error', 'Gagal memproses pengembalian: ' . $e->getMessage());
    redirect('/modules/petugas/peminjaman/detail.php?id=' . $id);
}
?>
