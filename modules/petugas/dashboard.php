<?php
session_start();
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!isPetugas()) {
    setMessage('error', 'Akses ditolak! Anda bukan petugas.');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Mengambil statistik untuk dashboard petugas
$stats = [
    'total_peminjaman' => $conn->query("SELECT COUNT(*) FROM peminjaman")->fetchColumn(),
    'peminjaman_aktif' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'dipinjam'")->fetchColumn(),
    'peminjaman_selesai' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'dikembalikan'")->fetchColumn(),
    'total_buku' => $conn->query("SELECT COUNT(*) FROM buku")->fetchColumn()
];

// Mengambil peminjaman terbaru untuk ditampilkan di tabel
$recent_loans = $conn->query("
    SELECT p.*, u.username, b.judul, b.penulis, b.penerbit 
    FROM peminjaman p 
    JOIN users u ON p.user_id = u.id 
    JOIN buku b ON p.buku_id = b.id 
    ORDER BY p.tanggal_peminjaman DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Mengambil informasi petugas yang sedang login
$petugas_id = $_SESSION['user_id'];
$petugas = $conn->query("SELECT * FROM users WHERE id = {$petugas_id}")->fetch(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Dashboard Petugas</h1>
        <div>
    <span class="badge bg-primary">Petugas: <?= htmlspecialchars($petugas['username']) ?></span>
</div>
    </div>

    <!-- Alert Message -->
    <?php showMessage(); ?>

    <!-- Stats Cards -->
    <div class="row mb-3">
        <!-- Total Peminjaman -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-primary mb-1 fw-bold">TOTAL PEMINJAMAN</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total_peminjaman']) ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-book-reader text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Peminjaman Aktif -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-1 fw-bold">PEMINJAMAN AKTIF</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['peminjaman_aktif']) ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-clock text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Peminjaman Selesai -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-1 fw-bold">PEMINJAMAN SELESAI</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['peminjaman_selesai']) ?></h2>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-check-circle text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Buku -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning mb-1 fw-bold">TOTAL BUKU</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total_buku']) ?></h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-book text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Peminjaman Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Peminjaman Terbaru</h6>
            <a href="../petugas/peminjaman/index.php" class="btn btn-primary btn-sm">
                <i class="fas fa-list me-1"></i> Lihat Semua
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Peminjam</th>
                            <th>Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Batas Kembali</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_loans)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">Belum ada data peminjaman</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_loans as $loan): ?>
                                <tr>
                                    <td><?= $loan['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($loan['username']) ?>&background=random"
                                                class="rounded-circle me-2"
                                                width="32"
                                                height="32"
                                                alt="Avatar">
                                            <?= htmlspecialchars($loan['username']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($loan['judul']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($loan['tanggal_peminjaman'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime('+7 days', strtotime($loan['tanggal_peminjaman']))) ?></td>
                                    <td>
                                        <?php if ($loan['status_peminjaman'] == 'dipinjam'): ?>
                                            <span class="badge rounded-pill bg-primary">Dipinjam</span>
                                        <?php elseif ($loan['status_peminjaman'] == 'dikembalikan'): ?>
                                            <span class="badge rounded-pill bg-success">Dikembalikan</span>
                                        <?php elseif ($loan['status_peminjaman'] == 'terlambat'): ?>
                                            <span class="badge rounded-pill bg-danger">Terlambat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="../petugas/peminjaman/detail.php?id=<?= $loan['id'] ?>"
                                                class="btn btn-sm btn-info text-white">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($loan['status_peminjaman'] == 'dipinjam'): ?>
                                                <a href="../petugas/pengembalian/process.php?id=<?= $loan['id'] ?>"
                                                    class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Daftar Peminjaman Terlambat -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Peminjaman Terlambat</h6>
                </div>
                <div class="card-body">
                    <?php
                    $overdue_loans = $conn->query("
                        SELECT p.*, u.username, b.judul
                        FROM peminjaman p 
                        JOIN users u ON p.user_id = u.id 
                        JOIN buku b ON p.buku_id = b.id 
                        WHERE p.status_peminjaman = 'dipinjam' 
                        AND DATE_ADD(p.tanggal_peminjaman, INTERVAL 7 DAY) < CURDATE()
                        ORDER BY p.tanggal_peminjaman ASC
                        LIMIT 5
                    ")->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if (empty($overdue_loans)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> Tidak ada peminjaman yang terlambat.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($overdue_loans as $loan): ?>
                                <?php
                                $due_date = date('Y-m-d', strtotime('+7 days', strtotime($loan['tanggal_peminjaman'])));
                                $days_late = floor((time() - strtotime($due_date)) / (60 * 60 * 24));
                                ?>
                                <a href="../petugas/peminjaman/detail.php?id=<?= $loan['id'] ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($loan['judul']) ?></h6>
                                        <small class="text-danger"><?= $days_late ?> hari terlambat</small>
                                    </div>
                                    <p class="mb-1">Peminjam: <?= htmlspecialchars($loan['username']) ?></p>
                                    <small>Batas kembali: <?= date('d/m/Y', strtotime($due_date)) ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-end">
                            <a href="../petugas/reports/keterlambatan.php" class="btn btn-sm btn-outline-danger">
                                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mengaktifkan menu sidebar yang aktif (Dashboard)
        const sidebarMenuItems = document.querySelectorAll('.sidebar-menu li a');
        sidebarMenuItems.forEach(item => {
            if (item.textContent.trim().includes('Dashboard')) {
                item.classList.add('active');
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>