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

// Mengambil data peminjaman berdasarkan ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    setMessage('error', 'ID peminjaman tidak valid.');
    redirect('/modules/petugas/peminjaman/index.php');
}

// Query untuk mengambil detail peminjaman beserta informasi terkait
$loan_query = "
    SELECT p.*, u.username, u.email, u.no_telepon, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.isbn, b.cover_img
    FROM peminjaman p 
    JOIN users u ON p.user_id = u.id 
    JOIN buku b ON p.buku_id = b.id 
    WHERE p.id = :id
";
$stmt = $conn->prepare($loan_query);
$stmt->execute(['id' => $id]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    setMessage('error', 'Data peminjaman tidak ditemukan.');
    redirect('/modules/petugas/peminjaman/index.php');
}

// Menghitung durasi peminjaman dan keterlambatan (jika ada)
$tgl_pinjam = strtotime($loan['tanggal_peminjaman']);
$tgl_batas = strtotime('+7 days', $tgl_pinjam);
$tgl_kembali = $loan['tanggal_dikembalikan'] ? strtotime($loan['tanggal_dikembalikan']) : time();

$durasi = ceil(($tgl_kembali - $tgl_pinjam) / (60 * 60 * 24));
$keterlambatan = $tgl_kembali > $tgl_batas ? ceil(($tgl_kembali - $tgl_batas) / (60 * 60 * 24)) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Peminjaman #<?= $id ?></title>
    <link rel="stylesheet" href="../../../assets/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .book-cover {
            max-height: 150px;
            margin-bottom: 15px;
        }
        .print-section {
            margin-bottom: 20px;
        }
        .print-footer {
            margin-top: 40px;
            text-align: center;
            font-style: italic;
        }
        
    .book-cover-container {
        min-height: 150px;
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .book-cover {
        transition: opacity 0.3s ease-in-out;
        max-width: 100%;
        object-fit: contain;
    }
    
    body::after {
        position: absolute;
        width: 0;
        height: 0;
        overflow: hidden;
        z-index: -1;
        content: url('../../../assets/img/books/default-book.jpg');
    }

        @media print {
            .no-print {
                display: none;
            }
            .page-break {
                page-break-before: always;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="print-header">
            <h2>BUKTI PEMINJAMAN BUKU</h2>
            <h4>Perpustakaan Digital</h4>
            <p>ID Peminjaman: #<?= $loan['id'] ?></p>
        </div>
        
        <div class="row">
            <div class="col-md-6 print-section">
                <h5 class="mb-3">Informasi Peminjaman</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Tanggal Peminjaman</th>
                        <td width="60%"><?= date('d/m/Y', strtotime($loan['tanggal_peminjaman'])) ?></td>
                    </tr>
                    <tr>
                        <th>Batas Pengembalian</th>
                        <td><?= date('d/m/Y', $tgl_batas) ?></td>
                    </tr>
                    <?php if ($loan['tanggal_dikembalikan']): ?>
                    <tr>
                        <th>Tanggal Dikembalikan</th>
                        <td><?= date('d/m/Y', strtotime($loan['tanggal_dikembalikan'])) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ($loan['status_peminjaman'] == 'dipinjam'): ?>
                                Dipinjam
                            <?php elseif ($loan['status_peminjaman'] == 'dikembalikan'): ?>
                                Dikembalikan
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($keterlambatan > 0): ?>
                    <tr>
                        <th>Keterlambatan</th>
                        <td><?= $keterlambatan ?> hari</td>
                    </tr>
                    <tr>
                        <th>Denda</th>
                        <td>Rp <?= number_format($loan['denda'] ?? $keterlambatan * 1000, 0, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="col-md-6 print-section">
                <h5 class="mb-3">Informasi Peminjam</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Nama</th>
                        <td width="60%"><?= htmlspecialchars($loan['username']) ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?= htmlspecialchars($loan['email']) ?></td>
                    </tr>
                    <?php if (!empty($loan['no_telepon'])): ?>
                    <tr>
                        <th>No. Telepon</th>
                        <td><?= htmlspecialchars($loan['no_telepon']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <div class="print-section">
    <h5 class="mb-3">Informasi Buku</h5>
    <div class="row">
        <div class="col-md-3">
            <?php
            // Tentukan path gambar dengan benar
            $coverImg = !empty($loan['cover_img']) ? $loan['cover_img'] : 'default-book.jpg';
            $imagePath = '../../../assets/img/books/' . $coverImg;
            
            // Jika cover_img adalah URL lengkap, gunakan langsung
            if (!empty($loan['cover_img']) && (strpos($loan['cover_img'], 'http://') === 0 || strpos($loan['cover_img'], 'https://') === 0)) {
                $imagePath = $loan['cover_img'];
            }
            ?>
            <div class="book-cover-container text-center" style="height: 150px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; border-radius: 0.25rem;">
                <img src="<?= $imagePath ?>"
                     alt="<?= htmlspecialchars($loan['judul']) ?>" 
                     class="img-fluid book-cover"
                     style="max-height: 150px; object-fit: contain;"
                     onerror="this.onerror=null; this.src='../../../assets/img/books/default-book.jpg';">
            </div>
        </div>
        <div class="col-md-9">
            <table class="table table-bordered">
                <tr>
                    <th width="30%">Judul</th>
                    <td width="70%"><?= htmlspecialchars($loan['judul']) ?></td>
                </tr>
                <tr>
                    <th>Penulis</th>
                    <td><?= htmlspecialchars($loan['penulis']) ?></td>
                </tr>
                <tr>
                    <th>Penerbit</th>
                    <td><?= htmlspecialchars($loan['penerbit']) ?></td>
                </tr>
                <tr>
                    <th>Tahun Terbit</th>
                    <td><?= htmlspecialchars($loan['tahun_terbit']) ?></td>
                </tr>
                <?php if (!empty($loan['isbn'])): ?>
                <tr>
                    <th>ISBN</th>
                    <td><?= htmlspecialchars($loan['isbn']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
        
        <?php if (!empty($loan['catatan'])): ?>
        <div class="print-section">
            <h5 class="mb-3">Catatan</h5>
            <div class="card">
                <div class="card-body">
                    <?= nl2br(htmlspecialchars($loan['catatan'])) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row print-section">
            <div class="col-md-6">
                <p>Petugas,</p>
                <br><br><br>
                <p>_______________________</p>
                <p><?= $_SESSION['username'] ?? 'Petugas Perpustakaan' ?></p>
            </div>
            <div class="col-md-6 text-end">
                <p>Peminjam,</p>
                <br><br><br>
                <p>_______________________</p>
                <p><?= htmlspecialchars($loan['username']) ?></p>
            </div>
        </div>
        
        <div class="print-footer">
            <p>Dokumen ini dicetak pada <?= date('d/m/Y H:i:s') ?></p>
            <p>Silahkan disimpan sebagai bukti peminjaman.</p>
        </div>
        
        <div class="text-center mt-4 no-print">
            <button id="print-bukti" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Cetak Bukti
            </button>
            <a href="detail.php?id=<?= $loan['id'] ?>" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <script src="../../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../../assets/js/script.js"></script>
</body>
</html>