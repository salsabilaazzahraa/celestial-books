<?php
session_start();
require_once '../../config/functions.php';
require_once '../../config/database.php';

if (!isAdmin()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Update status terlambat sama seperti di peminjaman.php
updateStatusTerlambat();

// Stats - menghapus active_loans seperti yang diminta
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_books' => $conn->query("SELECT COUNT(*) FROM buku")->fetchColumn(),
    'total_categories' => $conn->query("SELECT COUNT(*) FROM kategori_buku")->fetchColumn()
];

// Query untuk daftar peminjaman seperti di peminjaman.php
$query = "
    SELECT 
        p.*, 
        u.username, 
        u.nama_lengkap, 
        b.judul, 
        b.penulis,
        pt.username as petugas_username
    FROM 
        peminjaman p
    JOIN 
        users u ON p.user_id = u.id
    JOIN 
        buku b ON p.buku_id = b.id
    LEFT JOIN 
        users pt ON p.petugas_id = pt.id
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mendapatkan statistik sama seperti di peminjaman.php
$stats_peminjaman = [
    'total_peminjaman' => $conn->query("SELECT COUNT(*) FROM peminjaman")->fetchColumn(),
    'sedang_dipinjam' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'dipinjam'")->fetchColumn(),
    'dikembalikan' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'dikembalikan'")->fetchColumn(),
    'terlambat' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'terlambat'")->fetchColumn()
];

include '../../includes/header.php';
?>

<style>
    .sidebar {
        background-color: #1e5abc;
        color: #ffffff;
        min-height: 100vh;
        width: 250px;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 100;
    }

    .main-content {
        margin-left: 250px;
        background-color: #f5f8ff;
        min-height: 100vh;
        padding: 1rem;
    }

    .sidebar-header {
        padding: 1rem;
        text-align: center;
    }

    .sidebar-logo {
        max-width: 100px;
        margin-bottom: 0.5rem;
    }

    .sidebar-menu li a {
        color: #ffffff;
        padding: 0.75rem 1.5rem;
        display: block;
        transition: background-color 0.3s;
    }

    .sidebar-menu li a:hover,
    .sidebar-menu li a.active {
        background-color: rgba(255, 255, 255, 0.1);
        border-left: 3px solid #ffffff;
        text-decoration: none;
    }

    .stats-card {
        transition: transform 0.3s;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }

    /* Membuat card lebih rapat dengan mengurangi margin */
    .col-md-3.mb-3 {
        margin-bottom: 0.5rem !important;
    }

    .alert-dismissible {
        margin-bottom: 1rem;
    }
</style>

<!-- Main Content -->
<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Dashboard Admin</h1>
    </div>

    <!-- Alert Messages -->
    <?php if ($message = getMessage()): ?>
        <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
            <?= $message['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row mb-3">
        <!-- Total Pengguna -->
        <div class="col-md-4 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-primary mb-1 fw-bold">TOTAL PENGGUNA</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total_users']) ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-users text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Buku -->
        <div class="col-md-4 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-1 fw-bold">TOTAL BUKU</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total_books']) ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-book text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Kategori -->
        <div class="col-md-4 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning mb-1 fw-bold">TOTAL KATEGORI</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total_categories']) ?></h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-folder text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Peminjaman Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Peminjaman</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="dataTable">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kode Peminjaman</th>
                            <th>Peminjam</th>
                            <th>Buku</th>
                            <th>Tanggal Peminjaman</th>
                            <th>Tanggal Pengembalian</th>
                            <th>Status</th>
                            <th>Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($peminjaman)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data peminjaman</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($peminjaman as $index => $pinjam): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($pinjam['kode_peminjaman']) ?></td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($pinjam['nama_lengkap']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($pinjam['username']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($pinjam['judul']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($pinjam['penulis']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= date('d-m-Y', strtotime($pinjam['tanggal_peminjaman'])) ?></td>
                                    <td>
                                        <?= $pinjam['tanggal_pengembalian'] ? date('d-m-Y', strtotime($pinjam['tanggal_pengembalian'])) : '-' ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = 'bg-secondary';
                                        if ($pinjam['status_peminjaman'] == 'dipinjam') {
                                            $badgeClass = 'bg-info';
                                        } elseif ($pinjam['status_peminjaman'] == 'dikembalikan') {
                                            $badgeClass = 'bg-success';
                                        } elseif ($pinjam['status_peminjaman'] == 'terlambat') {
                                            $badgeClass = 'bg-danger';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($pinjam['status_peminjaman']) ?></span>
                                    </td>
                                    <td>
                                        <?= $pinjam['denda'] > 0 ? 'Rp ' . number_format($pinjam['denda'], 0, ',', '.') : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Memodifikasi class dari sidebar agar sesuai dengan desain yang diinginkan
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.add('bg-primary');
        }

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