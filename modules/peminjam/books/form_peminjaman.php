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

// Ambil detail user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Form Peminjaman Buku</h5>
                </div>
                <div class="card-body">
                    <form action="process_peminjaman.php" method="POST">
                        <input type="hidden" name="buku_id" value="<?= $book['id'] ?>">
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <?php
                                $coverImg = !empty($book['cover_img']) ? $book['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                                $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                                ?>
                                <img src="<?= $imagePath ?>" 
                                     class="img-fluid rounded shadow-sm" 
                                     alt="<?= htmlspecialchars($book['judul']) ?>"
                                     style="width: 100%; object-fit: contain; background-color: #f8f9fa; max-height: 300px;"
                                     onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                            </div>
                            <div class="col-md-8">
                                <h4><?= htmlspecialchars($book['judul']) ?></h4>
                                <p class="text-muted"><?= htmlspecialchars($book['penulis']) ?></p>
                                
                                <div class="mb-2">
                                    <span class="badge bg-primary"><?= htmlspecialchars($book['nama_kategori']) ?></span>
                                </div>
                                
                                <p><strong>ISBN:</strong> <?= htmlspecialchars($book['isbn']) ?></p>
                                <p><strong>Stok:</strong> <?= htmlspecialchars($book['stok']) ?></p>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama Peminjam</label>
                                    <input type="text" class="form-control" id="nama" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telepon" class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" id="telepon" value="<?= htmlspecialchars($user['no_telepon']) ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tanggal_peminjaman" class="form-label">Tanggal Peminjaman</label>
                                    <input type="date" class="form-control" id="tanggal_peminjaman" name="tanggal_peminjaman" value="<?= date('Y-m-d') ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tanggal_pengembalian" class="form-label">Tanggal Pengembalian</label>
                                    <?php
                                    // Menghitung tanggal pengembalian (7 hari dari sekarang)
                                    $return_date = date('Y-m-d', strtotime('+7 days'));
                                    ?>
                                    <input type="date" class="form-control" id="tanggal_pengembalian" name="tanggal_pengembalian" value="<?= $return_date ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Durasi peminjaman adalah 7 hari. Keterlambatan pengembalian akan dikenakan denda sesuai ketentuan.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-book me-1"></i>Konfirmasi Peminjaman
                            </button>
                            <a href="detail.php?id=<?= $book_id ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>