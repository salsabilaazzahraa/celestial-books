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

// Ambil riwayat peminjaman
$stmt = $conn->prepare("
    SELECT p.*, b.judul, b.penulis, b.penerbit
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    WHERE p.user_id = ?
    ORDER BY p.tanggal_peminjaman DESC
");
$stmt->execute([$_SESSION['user_id']]);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0 text-primary">Riwayat Peminjaman</h5>
                </div>
                <div class="col text-end">
                    <a href="#" onclick="window.print()" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kode Peminjaman</th>
                            <th>Judul Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Tanggal Kembali</th>
                            <th>Status</th>
                            <th>Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td><?= htmlspecialchars($loan['kode_peminjaman']) ?></td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($loan['judul']) ?></strong>
                                    </div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($loan['penulis']) ?> - 
                                        <?= htmlspecialchars($loan['penerbit']) ?>
                                    </small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($loan['tanggal_peminjaman'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($loan['tanggal_pengembalian'])) ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'dipinjam' => 'primary',
                                        'dikembalikan' => 'success',
                                        'terlambat' => 'danger'
                                    ];
                                    $status = $loan['status_peminjaman'];
                                    $class = $status_class[$status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $class ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($loan['denda'] > 0): ?>
                                        <span class="text-danger">
                                            Rp <?= number_format($loan['denda'], 0, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($loans)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data peminjaman</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .navbar, .btn, footer {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .container-fluid {
        padding: 0 !important;
    }
}
</style>

<?php include '../../../includes/footer.php'; ?>
