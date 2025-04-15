<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPeminjam()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Mengambil detail buku
$book_id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("
    SELECT b.*, k.nama_kategori 
    FROM buku b 
    JOIN kategori_buku_relasi kr ON b.id = kr.buku_id
    JOIN kategori_buku k ON kr.kategori_id = k.id 
    WHERE b.id = ?
");
$stmt->execute([$book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    setMessage('error', 'Buku tidak ditemukan!');
    redirect('/modules/peminjam/books/index.php');
}

// Cek apakah user sudah meminjam buku ini
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM peminjaman 
    WHERE user_id = ? AND buku_id = ? AND status_peminjaman = 'dipinjam'
");
$stmt->execute([$_SESSION['user_id'], $book_id]);
$already_borrowed = $stmt->fetchColumn() > 0;

// Cek jumlah peminjaman aktif
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM peminjaman 
    WHERE user_id = ? AND status_peminjaman = 'dipinjam'
");
$stmt->execute([$_SESSION['user_id']]);
$active_loans = $stmt->fetchColumn();

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
        <div class="card-body">
    <div class="row">
        <div class="col-md-4">
            <?php
            $coverImg = !empty($book['cover_img']) ? $book['cover_img'] : 'book' . rand(11, 15) . '.jpg';
            $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
            ?>
            <img src="<?= $imagePath ?>" 
                 class="img-fluid rounded shadow-sm" 
                 alt="<?= htmlspecialchars($book['judul']) ?>"
                 style="width: 100%; object-fit: contain; background-color: #f8f9fa; max-height: 500px;"
                 onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
        </div>
        <div class="col-md-8">
                            <h2 class="mb-3"><?= htmlspecialchars($book['judul']) ?></h2>
                            <div class="mb-3">
                                <span class="badge bg-primary"><?= htmlspecialchars($book['nama_kategori']) ?></span>
                            </div>
                            <table class="table">
                                <tr>
                                    <th width="150">Penulis</th>
                                    <td><?= htmlspecialchars($book['penulis']) ?></td>
                                </tr>
                                <tr>
                                    <th>Penerbit</th>
                                    <td><?= htmlspecialchars($book['penerbit']) ?></td>
                                </tr>
                                <tr>
                                    <th>Tahun Terbit</th>
                                    <td><?= htmlspecialchars($book['tahun_terbit']) ?></td>
                                </tr>
                                <tr>
                                    <th>ISBN</th>
                                    <td><?= htmlspecialchars($book['isbn']) ?></td>
                                </tr>
                                <tr>
                                    <th>Stok</th>
                                    <td><?= htmlspecialchars($book['stok']) ?></td>
                                </tr>
                            </table>
                            <div class="mb-3">
                                <h5>Deskripsi:</h5>
                                <p><?= nl2br(htmlspecialchars($book['deskripsi'])) ?></p>
                            </div>
                            
                            <?php if ($book['stok'] > 0 && !$already_borrowed && $active_loans < MAX_PEMINJAMAN): ?>
                                <a href="form_peminjaman.php?id=<?= $book['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-book me-1"></i>Pinjam Buku
                                </a>
                            <?php elseif ($already_borrowed): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-info-circle me-1"></i>Anda sedang meminjam buku ini
                                </div>
                            <?php elseif ($active_loans >= MAX_PEMINJAMAN): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-info-circle me-1"></i>Anda telah mencapai batas maksimal peminjaman
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle me-1"></i>Stok buku habis
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>
