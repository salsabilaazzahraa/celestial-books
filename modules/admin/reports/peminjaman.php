<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isAdmin() && !isPetugas()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();
updateStatusTerlambat();
// Proses filter dan pencarian
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$tanggal_awal = isset($_GET['tanggal_awal']) ? sanitize($_GET['tanggal_awal']) : '';
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? sanitize($_GET['tanggal_akhir']) : '';

// Base query
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
    WHERE 
        1=1
";

// Filter berdasarkan pencarian
if (!empty($search)) {
    $query .= " AND (u.username LIKE :search OR u.nama_lengkap LIKE :search OR b.judul LIKE :search OR b.penulis LIKE :search OR p.kode_peminjaman LIKE :search)";
}

// Filter berdasarkan status
if (!empty($status)) {
    $query .= " AND p.status_peminjaman = :status";
}

// Filter berdasarkan tanggal
if (!empty($tanggal_awal)) {
    $query .= " AND DATE(p.tanggal_peminjaman) >= :tanggal_awal";
}

if (!empty($tanggal_akhir)) {
    $query .= " AND DATE(p.tanggal_peminjaman) <= :tanggal_akhir";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);

if (!empty($search)) {
    $param = "%{$search}%";
    $stmt->bindParam(':search', $param);
}

if (!empty($status)) {
    $stmt->bindParam(':status', $status);
}

if (!empty($tanggal_awal)) {
    $stmt->bindParam(':tanggal_awal', $tanggal_awal);
}

if (!empty($tanggal_akhir)) {
    $stmt->bindParam(':tanggal_akhir', $tanggal_akhir);
}

$stmt->execute();
$peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mendapatkan statistik
$stats = [
    'total_peminjaman' => $conn->query("SELECT COUNT(*) FROM peminjaman")->fetchColumn(),
    'sedang_dipinjam' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'dipinjam'")->fetchColumn(),
    'dikembalikan' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'dikembalikan'")->fetchColumn(),
    'terlambat' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'terlambat'")->fetchColumn()
];

include '../../../includes/header.php';
?>

<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Laporan Peminjaman</h1>
        <div>
            <!-- Tombol Export Excel dan PDF telah dihapus -->
            <button type="button" class="btn btn-primary" id="btn-print">
                <i class="fas fa-print me-2"></i> Print
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message = getMessage()): ?>
        <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
            <?= $message['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter and Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status Peminjaman</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="dipinjam" <?= $status == 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                        <option value="dikembalikan" <?= $status == 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                        <option value="terlambat" <?= $status == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="<?= $tanggal_awal ?>">
                </div>
                <div class="col-md-2">
                    <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="<?= $tanggal_akhir ?>">
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Pencarian</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Cari berdasarkan judul, penulis, kode..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                            <i class="fas fa-book text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sedang Dipinjam -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-1 fw-bold">SEDANG DIPINJAM</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['sedang_dipinjam']) ?></h2>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-book-reader text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dikembalikan -->
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

        <!-- Terlambat -->
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

<!-- Area untuk mencetak laporan -->
<div id="print-area" class="d-none">
    <div class="text-center mb-4">
        <h2>LAPORAN PEMINJAMAN BUKU</h2>
        <p>Perpustakaan Digital - Celestial Books</p>
        <?php if (!empty($tanggal_awal) && !empty($tanggal_akhir)): ?>
            <p>Periode: <?= date('d-m-Y', strtotime($tanggal_awal)) ?> s/d <?= date('d-m-Y', strtotime($tanggal_akhir)) ?></p>
        <?php endif; ?>
        <?php if (!empty($status)): ?>
            <p>Status: <?= ucfirst($status) ?></p>
        <?php endif; ?>
    </div>

    <table class="table table-bordered" border="1" cellpadding="5" cellspacing="0" width="100%">
        <thead>
            <tr style="background-color: #f8f9fa;">
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
                            <?= htmlspecialchars($pinjam['nama_lengkap']) ?> (<?= htmlspecialchars($pinjam['username']) ?>)
                        </td>
                        <td>
                            <?= htmlspecialchars($pinjam['judul']) ?> - <?= htmlspecialchars($pinjam['penulis']) ?>
                        </td>
                        <td><?= date('d-m-Y', strtotime($pinjam['tanggal_peminjaman'])) ?></td>
                        <td>
                            <?= $pinjam['tanggal_pengembalian'] ? date('d-m-Y', strtotime($pinjam['tanggal_pengembalian'])) : '-' ?>
                        </td>
                        <td><?= ucfirst($pinjam['status_peminjaman']) ?></td>
                        <td>
                            <?= $pinjam['denda'] > 0 ? 'Rp ' . number_format($pinjam['denda'], 0, ',', '.') : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Ubah bagian ini di area print report -->
<div class="mt-4 text-end">
    <p>
        Dicetak pada: <?= date('d-m-Y H:i:s') ?><br>
        <?php if (isset($_SESSION['nama_lengkap'])): ?>
            Petugas: <?= $_SESSION['nama_lengkap'] ?>
        <?php elseif (isset($_SESSION['user']) && isset($_SESSION['user']['nama_lengkap'])): ?>
            Petugas: <?= $_SESSION['user']['nama_lengkap'] ?>
        <?php else: ?>
            Petugas: Unknown
        <?php endif; ?>
    </p>
</div>

<!-- JavaScript untuk Print -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mengaktifkan menu sidebar yang aktif
        const sidebarMenuItems = document.querySelectorAll('.sidebar-menu li a');
        sidebarMenuItems.forEach(item => {
            if (item.textContent.trim().includes('Laporan Peminjaman')) {
                item.classList.add('active');
            }
        });

        // Function untuk print
        document.getElementById('btn-print').addEventListener('click', function() {
            // Mendapatkan elemen untuk dicetak
            const printArea = document.getElementById('print-area');
            
            // Clone elemen print-area dan hapus kelas d-none agar terlihat
            const printAreaClone = printArea.cloneNode(true);
            printAreaClone.classList.remove('d-none');
            
            // Buat elemen style untuk mengatur tampilan cetak
            const style = document.createElement('style');
            style.innerHTML = `
                @media print {
                    body * {
                        visibility: hidden;
                    }
                    #print-content, #print-content * {
                        visibility: visible;
                    }
                    #print-content {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                    }
                }
            `;
            
            // Buat div untuk konten cetak
            const printContent = document.createElement('div');
            printContent.id = 'print-content';
            printContent.appendChild(style);
            printContent.appendChild(printAreaClone);
            
            // Append elemen sementara ke body
            document.body.appendChild(printContent);
            
            // Jalankan fungsi print
            window.print();
            
            // Hapus elemen setelah print
            document.body.removeChild(printContent);
        });
    });
</script>

<?php include '../../../includes/footer.php'; ?>