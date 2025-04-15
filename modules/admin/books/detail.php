<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isAdmin()) {
    setMessage('danger', 'Akses ditolak! Anda bukan admin.');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Ambil ID buku dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query untuk mendapatkan detail buku beserta kategorinya
$query = "SELECT b.*, GROUP_CONCAT(kb.nama_kategori SEPARATOR ', ') as kategori 
          FROM buku b
          LEFT JOIN kategori_buku_relasi kbr ON b.id = kbr.buku_id
          LEFT JOIN kategori_buku kb ON kbr.kategori_id = kb.id
          WHERE b.id = :id
          GROUP BY b.id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$buku = $stmt->fetch(PDO::FETCH_ASSOC);

// Cek apakah buku ditemukan
if (!$buku) {
    setMessage('danger', 'Buku tidak ditemukan!');
    redirect('../books/index.php');
}

include '../../../includes/header.php';
?>
<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Detail Buku</h1>
        <a href="../books/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="<?= $buku['cover_img'] ? BASE_URL . '/assets/img/books/' . $buku['cover_img'] : BASE_URL . '/assets/img/books/book' . rand(11, 15) . '.jpg' ?>" 
                         class="img-fluid rounded mb-3" 
                         alt="Cover Buku">
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title mb-4"><?= htmlspecialchars($buku['judul']) ?></h2>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Kode Buku:</strong>
                            <p><code><?= htmlspecialchars($buku['kode_buku']) ?></code></p>
                        </div>
                        <div class="col-md-6">
                            <strong>ISBN:</strong>
                            <p><?= htmlspecialchars($buku['isbn'] ?: '-') ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Penulis:</strong>
                            <p><?= htmlspecialchars($buku['penulis']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Penerbit:</strong>
                            <p><?= htmlspecialchars($buku['penerbit']) ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Tahun Terbit:</strong>
                            <p><?= htmlspecialchars($buku['tahun_terbit']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Stok:</strong>
                            <p>
                                <span class="badge <?= $buku['stok'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $buku['stok'] ?> buku
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Kategori:</strong>
                        <p><?= htmlspecialchars($buku['kategori'] ?: '-') ?></p>
                    </div>

                    <div class="mb-3">
                        <strong>Deskripsi:</strong>
                        <p><?= htmlspecialchars($buku['deskripsi'] ?: 'Tidak ada deskripsi') ?></p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <strong>Ditambahkan:</strong>
                            <p><?= date('d M Y H:i', strtotime($buku['created_at'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Terakhir Diperbarui:</strong>
                            <p><?= date('d M Y H:i', strtotime($buku['updated_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>