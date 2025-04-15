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

// Filter dan pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Query dasar untuk peminjaman yang masih dipinjam
$query = "
    SELECT p.*, u.username, b.judul, b.penulis, b.cover_img 
    FROM peminjaman p 
    JOIN users u ON p.user_id = u.id 
    JOIN buku b ON p.buku_id = b.id 
    WHERE p.status_peminjaman = 'dipinjam'
";

// Tambahkan filter pencarian
if (!empty($search)) {
    $query .= " AND (u.username LIKE :search OR b.judul LIKE :search OR b.penulis LIKE :search)";
}

// Tambahkan pengurutan
if ($sort == 'oldest') {
    $query .= " ORDER BY p.tanggal_peminjaman ASC";
} elseif ($sort == 'newest') {
    $query .= " ORDER BY p.tanggal_peminjaman DESC";
} elseif ($sort == 'due_date') {
    $query .= " ORDER BY DATE_ADD(p.tanggal_peminjaman, INTERVAL 7 DAY) ASC";
}

// Eksekusi query dengan parameter
$stmt = $conn->prepare($query);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam);
}

$stmt->execute();
$active_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../../includes/header.php';
?>

<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Pengembalian Buku</h1>
        <a href="../peminjaman/index.php" class="btn btn-outline-primary">
            <i class="fas fa-list"></i> Daftar Peminjaman
        </a>
    </div>

    <!-- Alert Message -->
    <?php showMessage(); ?>

    <!-- Filter dan Pencarian -->
    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" class="d-flex">
                <input type="text" class="form-control me-2" id="search" name="search"
                    placeholder="Cari peminjam atau judul buku..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Daftar Peminjaman Aktif -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Peminjaman Aktif</h6>
        </div>
        <div class="card-body">
            <?php if (empty($active_loans)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i> Tidak ada peminjaman aktif yang perlu dikembalikan.
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                    <?php foreach ($active_loans as $loan): ?>
                        <?php
                        // Hitung tanggal batas pengembalian (7 hari setelah peminjaman)
                        $due_date = date('Y-m-d', strtotime('+7 days', strtotime($loan['tanggal_peminjaman'])));

                        // Cek apakah sudah terlambat
                        $is_late = strtotime($due_date) < time();
                        $days_late = $is_late ? floor((time() - strtotime($due_date)) / (60 * 60 * 24)) : 0;

                        // Hitung denda jika terlambat (Rp 1.000 per hari)
                        $denda = $is_late ? $days_late * 1000 : 0;
                        ?>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="<?= !empty($loan['cover_img']) ? '../../../assets/img/books/' . $loan['cover_img'] : 'https://via.placeholder.com/150x200?text=No+Cover' ?>"
                                            class="img-fluid rounded-start h-100 object-fit-cover"
                                            alt="<?= htmlspecialchars($loan['judul']) ?>"
                                            style="max-height: 200px">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <span class="badge bg-primary mb-2">ID: <?= $loan['id'] ?></span>
                                            <h5 class="card-title"><?= htmlspecialchars($loan['judul']) ?></h5>
                                            <p class="card-text text-muted small mb-2"><?= htmlspecialchars($loan['penulis']) ?></p>

                                            <div class="mb-2">
                                                <i class="fas fa-user text-secondary me-1"></i>
                                                <span><?= htmlspecialchars($loan['username']) ?></span>
                                            </div>

                                            <div class="mb-2">
                                                <i class="fas fa-calendar-alt text-secondary me-1"></i>
                                                <span>Dipinjam: <?= date('d/m/Y', strtotime($loan['tanggal_peminjaman'])) ?></span>
                                            </div>

                                            <div class="mb-2">
                                                <i class="fas fa-calendar-check text-secondary me-1"></i>
                                                <span>Batas Kembali: <?= date('d/m/Y', strtotime($due_date)) ?></span>
                                                <?php if ($is_late): ?>
                                                    <span class="badge bg-danger ms-1">Terlambat <?= $days_late ?> hari</span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($is_late): ?>
                                                <div class="alert alert-warning py-1 px-2 mb-2 small">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Denda: Rp <?= number_format($denda, 0, ',', '.') ?>
                                                </div>
                                            <?php endif; ?>

                                            <a href="process.php?id=<?= $loan['id'] ?>" class="btn btn-success btn-sm w-100 mt-2">
                                                <i class="fas fa-undo me-1"></i> Proses Pengembalian
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto submit when barcode is scanned
        const barcodeInput = document.getElementById('barcode');
        if (barcodeInput) {
            barcodeInput.addEventListener('input', function(e) {
                if (e.target.value.length >= 5) {
                    document.getElementById('barcodeForm').submit();
                }
            });
        }
    });
</script>

<?php include '../../../includes/footer.php'; ?>