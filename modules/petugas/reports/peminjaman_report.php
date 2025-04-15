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

// Filter data
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_date_start = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$filter_date_end = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Query dasar
$query = "
    SELECT p.*, u.username, u.email, b.judul, b.penulis, b.penerbit
    FROM peminjaman p 
    JOIN users u ON p.user_id = u.id 
    JOIN buku b ON p.buku_id = b.id 
    WHERE 1=1
";

// Menambahkan filter ke query
if ($filter_status !== 'all') {
    $query .= " AND p.status_peminjaman = :status";
}

if (!empty($filter_date_start)) {
    $query .= " AND p.tanggal_peminjaman >= :date_start";
}

if (!empty($filter_date_end)) {
    $query .= " AND p.tanggal_peminjaman <= :date_end";
}

if ($filter_user > 0) {
    $query .= " AND p.user_id = :user_id";
}

$query .= " ORDER BY p.tanggal_peminjaman DESC";

$stmt = $conn->prepare($query);

// Binding parameter
if ($filter_status !== 'all') {
    $stmt->bindParam(':status', $filter_status);
}

if (!empty($filter_date_start)) {
    $stmt->bindParam(':date_start', $filter_date_start);
}

if (!empty($filter_date_end)) {
    $stmt->bindParam(':date_end', $filter_date_end);
}

if ($filter_user > 0) {
    $stmt->bindParam(':user_id', $filter_user);
}

$stmt->execute();
$peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mengambil daftar peminjam untuk filter
$users = $conn->query("SELECT id, username FROM users WHERE role = 'peminjam' ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// Set print mode jika diperlukan
$print_mode = isset($_GET['print']) && $_GET['print'] == 'true';

// Jika mode cetak, gunakan layout khusus cetak
if ($print_mode) {
    include '../../includes/header_print.php';
} else {
    include '../../includes/header.php';
}
?>

<div class="container-fluid p-3 <?= $print_mode ? 'print-container' : '' ?>">
    <?php if (!$print_mode): ?>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Laporan Peminjaman</h1>
        <div>
            <button id="print-report" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Cetak Laporan
            </button>
            <a href="../petugas/dashboard.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Alert Message -->
    <?php showMessage(); ?>

    <!-- Filter Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="dipinjam" <?= $filter_status === 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                        <option value="dikembalikan" <?= $filter_status === 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_start" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="date_start" id="date_start" class="form-control" value="<?= $filter_date_start ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_end" class="form-label">Tanggal Akhir</label>
                    <input type="date" name="date_end" id="date_end" class="form-control" value="<?= $filter_date_end ?>">
                </div>
                <div class="col-md-3">
                    <label for="user_id" class="form-label">Peminjam</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="0">Semua Peminjam</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $filter_user === $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <a href="peminjaman_report.php" class="btn btn-secondary ms-2">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Print Header -->
    <div class="text-center mb-4">
        <h2 class="mb-1">LAPORAN PEMINJAMAN BUKU</h2>
        <h4 class="mb-3">PERPUSTAKAAN CELESTIAL</h4>
        <hr>
        <p>Periode: 
            <?= !empty($filter_date_start) ? date('d/m/Y', strtotime($filter_date_start)) : 'Semua Tanggal' ?> 
            s/d 
            <?= !empty($filter_date_end) ? date('d/m/Y', strtotime($filter_date_end)) : 'Sekarang' ?>
        </p>
        <p>
            Status: <?= $filter_status === 'all' ? 'Semua Status' : ucfirst($filter_status) ?>
            <?php if ($filter_user > 0): ?>
                <?php
                    $user_name = "";
                    foreach ($users as $user) {
                        if ($user['id'] == $filter_user) {
                            $user_name = $user['username'];
                            break;
                        }
                    }
                ?>
                | Peminjam: <?= htmlspecialchars($user_name) ?>
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Report Table -->
    <div class="card border-0 shadow-sm mb-4">
        <?php if (!$print_mode): ?>
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Peminjaman</h6>
        </div>
        <?php endif; ?>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal Peminjaman</th>
                            <th>Judul Buku</th>
                            <th>Peminjam</th>
                            <th>Status</th>
                            <th>Tanggal Kembali</th>
                            <th>Denda</th>
                            <?php if (!$print_mode): ?>
                            <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($peminjaman)): ?>
                            <tr>
                                <td colspan="<?= $print_mode ? '7' : '8' ?>" class="text-center py-3">Tidak ada data peminjaman</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($peminjaman as $p): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['tanggal_peminjaman'])) ?></td>
                                    <td><?= htmlspecialchars($p['judul']) ?></td>
                                    <td><?= htmlspecialchars($p['username']) ?></td>
                                    <td>
                                        <?php if ($p['status_peminjaman'] == 'dipinjam'): ?>
                                            <?php 
                                            $batas_kembali = strtotime('+7 days', strtotime($p['tanggal_peminjaman']));
                                            $terlambat = time() > $batas_kembali;
                                            ?>
                                            <span class="badge bg-<?= $terlambat ? 'danger' : 'primary' ?>">
                                                <?= $terlambat ? 'Terlambat' : 'Dipinjam' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Dikembalikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['status_peminjaman'] == 'dipinjam'): ?>
                                            <?= date('d/m/Y', strtotime('+7 days', strtotime($p['tanggal_peminjaman']))) ?>
                                            <?php if ($terlambat): ?>
                                                <span class="badge bg-danger">
                                                    <?= ceil((time() - $batas_kembali) / (60 * 60 * 24)) ?> hari
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?= date('d/m/Y', strtotime($p['tanggal_dikembalikan'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['denda']) && $p['denda'] > 0): ?>
                                            <span class="text-danger">Rp <?= number_format($p['denda'], 0, ',', '.') ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <?php if (!$print_mode): ?>
                                    <td>
                                        <a href="../petugas/peminjaman/detail.php?id=<?= $p['id'] ?>" class="btn btn-info btn-sm text-white">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($print_mode): ?>
            <!-- Tanda tangan -->
            <div class="row mt-5">
                <div class="col-md-6"></div>
                <div class="col-md-6 text-center">
                    <p>________________________, <?= date('d M Y') ?></p>
                    <p>Petugas Perpustakaan</p>
                    <br><br><br>
                    <p><u><?= htmlspecialchars($_SESSION['username']) ?></u></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$print_mode): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mengaktifkan menu sidebar
        const sidebarMenuItems = document.querySelectorAll('.sidebar-menu li a');
        sidebarMenuItems.forEach(item => {
            if (item.textContent.trim().includes('Laporan')) {
                item.classList.add('active');
            }
        });
        
        // Tambahkan event listener untuk tombol cetak
        document.getElementById('print-report').addEventListener('click', function() {
            // Membuat URL dengan parameter yang sama dan menambahkan parameter print=true
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('print', 'true');
            
            // Buka jendela baru dengan URL yang telah dimodifikasi
            const printWindow = window.open(currentUrl.toString(), '_blank');
            
            // Jalankan perintah cetak setelah halaman dimuat
            printWindow.onload = function() {
                setTimeout(function() {
                    printWindow.print();
                }, 500);
            };
        });
    });
</script>
<?php else: ?>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const printBtn = document.getElementById('print-bukti');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
});
</script>

<?php endif; ?>

<?php 
if (!$print_mode) {
    include '../../includes/footer.php';
}
?>