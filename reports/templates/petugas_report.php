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
updateStatusTerlambat();
// Filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Base query
$query = "
    SELECT p.*, u.username, b.judul, b.penulis, b.penerbit 
    FROM peminjaman p 
    JOIN users u ON p.user_id = u.id 
    JOIN buku b ON p.buku_id = b.id 
    WHERE p.tanggal_peminjaman BETWEEN :start_date AND :end_date
";

// Add status filter if not 'all'
if ($status !== 'all') {
    $query .= " AND p.status_peminjaman = :status";
}

$query .= " ORDER BY p.tanggal_peminjaman DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);

if ($status !== 'all') {
    $stmt->bindParam(':status', $status);
}

$stmt->execute();
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics for report
$stats = [
    'total' => count($loans),
    'dipinjam' => 0,
    'dikembalikan' => 0,
    'terlambat' => 0
];

foreach ($loans as $loan) {
    if ($loan['status_peminjaman'] == 'dipinjam') {
        $stats['dipinjam']++;
    } elseif ($loan['status_peminjaman'] == 'dikembalikan') {
        $stats['dikembalikan']++;
    } elseif ($loan['status_peminjaman'] == 'terlambat') {
        $stats['terlambat']++;
    }
}

// Get petugas info
$petugas_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :petugas_id");
$stmt->bindParam(':petugas_id', $petugas_id);
$stmt->execute();
$petugas = $stmt->fetch(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Laporan Peminjaman</h1>
        <div>
            <button id="print-report" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Alert Message -->
    <?php showMessage(); ?>

    <!-- Report Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-primary mb-1 fw-bold">TOTAL PEMINJAMAN</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total']) ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-book-reader text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-1 fw-bold">DIKEMBALIKAN</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['dikembalikan']) ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-1 fw-bold">MASIH DIPINJAM</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['dipinjam']) ?></h2>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-clock text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-danger mb-1 fw-bold">TERLAMBAT</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['terlambat']) ?></h2>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-exclamation-circle text-danger fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="report-content" class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Data Peminjaman</h6>
            <span class="badge bg-primary">Total: <?= number_format($stats['total']) ?> peminjaman</span>
        </div>
        <div class="card-body">
            <!-- Report Header (untuk cetakan) -->
            <div class="report-header d-none">
                <div class="text-center mb-4">
                    <h3><?= APP_NAME ?></h3>
                    <h5>LAPORAN PEMINJAMAN BUKU</h5>
                    <p>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <p>Petugas: <?= htmlspecialchars($petugas['username'] ?? 'Administrator') ?></p>
                        <p>Tanggal Cetak: <?= date('d/m/Y H:i:s') ?></p>
                    </div>
                    <div class="col-6 text-end">
                        <p>Total Peminjaman: <?= number_format($stats['total']) ?></p>
                        <p>Status: <?= $status == 'all' ? 'Semua Status' : ucfirst($status) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Peminjam</th>
                            <th>Judul Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Batas Kembali</th>
                            <th>Tanggal Kembali</th>
                            <th>Status</th>
                            <!-- Kolom Petugas dihapus dari sini -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($loans)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">Tidak ada data peminjaman sesuai filter</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($loans as $loan): ?>
                                <?php
                                $due_date = date('Y-m-d', strtotime('+7 days', strtotime($loan['tanggal_peminjaman'])));
                                // Kode untuk mendapatkan nama petugas dihapus karena tidak digunakan lagi
                                ?>
                                <tr>
                                    <td><?= $loan['id'] ?></td>
                                    <td><?= htmlspecialchars($loan['username']) ?></td>
                                    <td><?= htmlspecialchars($loan['judul']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($loan['tanggal_peminjaman'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($due_date)) ?></td>
                                    <td>
                                        <?= $loan['tanggal_dikembalikan'] ? date('d/m/Y', strtotime($loan['tanggal_dikembalikan'])) : '-' ?>
                                    </td>
                                    <td>
                                        <?php if ($loan['status_peminjaman'] == 'dipinjam'): ?>
                                            <span class="badge rounded-pill bg-primary">Dipinjam</span>
                                        <?php elseif ($loan['status_peminjaman'] == 'dikembalikan'): ?>
                                            <span class="badge rounded-pill bg-success">Dikembalikan</span>
                                        <?php elseif ($loan['status_peminjaman'] == 'terlambat'): ?>
                                            <span class="badge rounded-pill bg-danger">Terlambat</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Kolom tampilan petugas dihapus dari sini -->
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Report Footer (untuk cetakan) -->
            <div class="report-footer d-none mt-5">
                <div class="row">
                    <div class="col-8"></div>
                    <div class="col-4 text-center">
                        <p>................., <?= date('d F Y') ?></p>
                        <p>Petugas Perpustakaan</p>
                        <div style="height: 80px;"></div>
                        <p><?= htmlspecialchars($petugas['username'] ?? 'Administrator') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        
        #report-content, #report-content * {
            visibility: visible;
        }
        
        .report-header, .report-footer {
            display: block !important;
        }
        
        #report-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            box-shadow: none !important;
        }
        
        .card-header, .btn, .no-print {
            display: none !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Print report
        const printBtn = document.getElementById('print-report');
        if (printBtn) {
            printBtn.addEventListener('click', function() {
                window.print();
            });
        }
        
        // Mengaktifkan menu sidebar yang aktif
        const sidebarMenuItems = document.querySelectorAll('.sidebar-menu li a');
        sidebarMenuItems.forEach(item => {
            // Reset semua menu dahulu
            if (item.textContent.trim().includes('Cetak Kartu') || 
                item.textContent.trim().includes('Laporan Peminjaman')) {
                item.classList.remove('active');
            }
            
            // Aktifkan menu Laporan Peminjaman saja
            if (item.textContent.trim().includes('Laporan Peminjaman')) {
                item.classList.add('active');
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>