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

// Proses form peminjaman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $buku_id = isset($_POST['buku_id']) ? intval($_POST['buku_id']) : 0;
        $tanggal_peminjaman = isset($_POST['tanggal_peminjaman']) ? $_POST['tanggal_peminjaman'] : date('Y-m-d');
        $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';

        // Validasi user dan buku
        if ($user_id <= 0 || $buku_id <= 0) {
            throw new Exception("Data peminjam atau buku tidak valid.");
        }

        // Cek apakah peminjam memiliki peminjaman aktif untuk buku yang sama
        $check_query = "SELECT COUNT(*) FROM peminjaman WHERE user_id = :user_id AND buku_id = :buku_id AND status_peminjaman = 'dipinjam'";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute(['user_id' => $user_id, 'buku_id' => $buku_id]);

        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("Peminjam masih memiliki peminjaman aktif untuk buku yang sama.");
        }

        // Cek stok buku
        $book_query = "SELECT stok FROM buku WHERE id = :id";
        $book_stmt = $conn->prepare($book_query);
        $book_stmt->execute(['id' => $buku_id]);
        $book = $book_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book || $book['stok'] <= 0) {
            throw new Exception("Stok buku tidak tersedia.");
        }

        // Mulai transaksi
        $conn->beginTransaction();

        // Insert data peminjaman
        $peminjaman_query = "
            INSERT INTO peminjaman (user_id, buku_id, tanggal_peminjaman, status_peminjaman, catatan, petugas_id)
            VALUES (:user_id, :buku_id, :tanggal_peminjaman, 'dipinjam', :catatan, :petugas_id)
        ";
        $peminjaman_stmt = $conn->prepare($peminjaman_query);
        $peminjaman_stmt->execute([
            'user_id' => $user_id,
            'buku_id' => $buku_id,
            'tanggal_peminjaman' => $tanggal_peminjaman,
            'catatan' => $catatan,
            'petugas_id' => $_SESSION['user_id']
        ]);

        $peminjaman_id = $conn->lastInsertId();

        // Update stok buku
        $update_query = "UPDATE buku SET stok = stok - 1 WHERE id = :id";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute(['id' => $buku_id]);

        // Insert riwayat peminjaman
        $riwayat_query = "
            INSERT INTO riwayat_peminjaman (peminjaman_id, status_perubahan, catatan, changed_by)
            VALUES (:peminjaman_id, 'dipinjam', 'Peminjaman baru', :changed_by)
        ";
        $riwayat_stmt = $conn->prepare($riwayat_query);
        $riwayat_stmt->execute([
            'peminjaman_id' => $peminjaman_id,
            'changed_by' => $_SESSION['user_id']
        ]);

        // Commit transaksi
        $conn->commit();

        setMessage('success', 'Peminjaman berhasil ditambahkan.');
        redirect("detail.php?id={$peminjaman_id}");
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollBack();

        setMessage('error', 'Gagal menambahkan peminjaman: ' . $e->getMessage());
        redirect('create.php');
    }
} else {
    setMessage('error', 'Metode request tidak valid.');
    redirect('index.php');
}
?>