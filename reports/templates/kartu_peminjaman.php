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

// Get user data for dropdown
$users = $conn->query("SELECT id, username, nama_lengkap FROM users WHERE role = 'peminjam' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Process form submission if user is selected
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$user_data = null;
$peminjaman = [];

if ($user_id) {
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id AND role = 'peminjam'");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        // Get peminjaman data
        $stmt = $conn->prepare("
            SELECT p.*, b.judul, b.penulis, b.isbn
            FROM peminjaman p
            JOIN buku b ON p.buku_id = b.id
            WHERE p.user_id = :user_id
            ORDER BY p.tanggal_peminjaman DESC
            LIMIT 10
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Cetak Kartu Anggota</h1>
        <?php if ($user_data): ?>
        <div>
            <button id="print-kartu" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Cetak Kartu
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Alert Message -->
    <?php showMessage(); ?>

    <!-- User Selection Form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pilih Anggota</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="user_id" class="form-label">Anggota</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">-- Pilih Anggota --</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= ($user_id == $user['id'] ? 'selected' : '') ?>>
                            <?= htmlspecialchars($user['username']) ?> <?= $user['nama_lengkap'] ? '- ' . htmlspecialchars($user['nama_lengkap']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Cari Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($user_data): ?>
    <!-- Kartu Anggota -->
    <div id="kartu-content" class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <!-- Bagian ini akan ditampilkan saat cetak -->
            <div class="row mb-4">
                <div class="col-md-12 text-center mb-4">
                    <h4 class="mb-0"><?= APP_NAME ?></h4>
                    <p class="mb-0">KARTU ANGGOTA PERPUSTAKAAN</p>
                    <hr class="my-2 mx-auto" style="width: 50%;">
                </div>
                
                <div class="col-md-12 mb-4">
                    <div class="row">
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150">ID Anggota</td>
                                    <td width="20">:</td>
                                    <td><strong><?= $user_data['id'] ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Nama Lengkap</td>
                                    <td>:</td>
                                    <td><strong><?= htmlspecialchars($user_data['nama_lengkap'] ?? $user_data['username']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Email</td>
                                    <td>:</td>
                                    <td><?= htmlspecialchars($user_data['email']) ?></td>
                                </tr>
                                <tr>
                                    <td>No. Telepon</td>
                                    <td>:</td>
                                    <td><?= htmlspecialchars($user_data['no_telepon'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td>Alamat</td>
                                    <td>:</td>
                                    <td><?= htmlspecialchars($user_data['alamat'] ?? '-') ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="bg-light p-3 rounded mb-2" style="height: 150px; width: 150px; margin: 0 auto;">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_data['username']) ?>&background=random&size=150"
                                    class="img-fluid"
                                    alt="Avatar">
                            </div>
                            <div class="mt-2">
                                <strong>Username: <?= htmlspecialchars($user_data['username']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Kartu -->
                <div class="col-md-12 mt-5">
                    <div class="row">
                        <div class="col-8">
                            <p>Catatan:</p>
                            <ol>
                                <li>Kartu ini berfungsi sebagai identitas anggota Perpustakaan</li>
                                <li>Maksimal peminjaman: 3 buku</li>
                                <li>Masa peminjaman: 7 hari</li>
                                <li>Denda keterlambatan: Rp 1.000/hari/buku</li>
                            </ol>
                        </div>
                        <div class="col-4 text-center">
                            <p>Dikeluarkan pada:</p>
                            <p><?= date('d F Y') ?></p>
                            <div style="height: 60px;"></div>
                            <p><strong>Petugas Perpustakaan</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        
        #kartu-content, #kartu-content * {
            visibility: visible;
        }
        
        #kartu-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Print kartu
        const printBtn = document.getElementById('print-kartu');
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
            
            // Aktifkan menu Cetak Kartu saja
            if (item.textContent.trim().includes('Cetak Kartu')) {
                item.classList.add('active');
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>