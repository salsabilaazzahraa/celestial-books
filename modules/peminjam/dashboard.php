<?php
session_start();
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!isPeminjam()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Mengambil data buku terbaru
$query = "
    SELECT b.*, k.nama_kategori 
    FROM buku b 
    LEFT JOIN kategori_buku_relasi kr ON b.id = kr.buku_id
    LEFT JOIN kategori_buku k ON kr.kategori_id = k.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
";
$stmt = $conn->prepare($query);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Selamat Datang, <?= $_SESSION['username'] ?>!</h1>
    </div>

    <!-- Buku Terbaru -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 font-weight-bold text-primary">Buku Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (empty($books)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>Belum ada buku yang tersedia.
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <?php
                                        // Gunakan cover_img jika ada, atau gunakan gambar default
                                        $coverImg = !empty($book['cover_img']) ? $book['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                                        $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                                        ?>
                                        <img src="<?= $imagePath ?>" 
                                        class="card-img-top" 
                                        alt="<?= htmlspecialchars($book['judul']) ?>"
                                        style="height: 300px; object-fit: contain; background-color: #f8f9fa;"
                                        onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                                        <div class="card-body">
                                            <h5 class="card-title text-truncate" title="<?= htmlspecialchars($book['judul']) ?>"><?= htmlspecialchars($book['judul']) ?></h5>
                                            <p class="card-text mb-1">
                                                <small class="text-muted">
                                                    <i class="fas fa-tag me-1"></i><?= $book['nama_kategori'] ? htmlspecialchars($book['nama_kategori']) : 'Tanpa Kategori' ?>
                                                </small>
                                            </p>
                                            <p class="card-text mb-1">
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($book['penulis']) ?>
                                                </small>
                                            </p>
                                            <p class="card-text mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-book me-1"></i>
                                                    Stok: <?= htmlspecialchars($book['stok']) ?>
                                                </small>
                                            </p>
                                            <p class="card-text text-truncate"><?= htmlspecialchars($book['deskripsi']) ?></p>
                                            <div class="mt-3">
                                                <a href="<?= BASE_URL ?>/modules/peminjam/books/detail.php?id=<?= $book['id'] ?>" 
                                                   class="btn btn-primary btn-sm w-100">
                                                    <i class="fas fa-info-circle me-1"></i>Detail
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
