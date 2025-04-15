<?php
session_start();
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../functions/helpers.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: '.BASE_URL.'/auth/login.php');
    exit;
}

// Inisialisasi variabel pesan
$message = '';
$messageType = '';

// Proses form tambah/edit kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Tambah kategori baru
            $name = trim($_POST['nama_kategori']);
            
            if (empty($name)) {
                $message = 'Nama kategori tidak boleh kosong';
                $messageType = 'danger';
            } else {
                // Cek apakah kategori sudah ada
                $stmt = $pdo->prepare("SELECT id FROM kategori_buku WHERE nama_kategori = ?");
                $stmt->execute([$name]);
                
                if ($stmt->rowCount() > 0) {
                    $message = 'Kategori dengan nama tersebut sudah ada';
                    $messageType = 'danger';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO kategori_buku (nama_kategori) VALUES (?)");
                    if ($stmt->execute([$name])) {
                        $message = 'Kategori berhasil ditambahkan';
                        $messageType = 'success';
                    } else {
                        $message = 'Gagal menambahkan kategori';
                        $messageType = 'danger';
                    }
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            // Edit kategori
            $id = (int)$_POST['id'];
            $name = trim($_POST['nama_kategori']);
            
            if (empty($name)) {
                $message = 'Nama kategori tidak boleh kosong';
                $messageType = 'danger';
            } else {
                // Cek apakah nama kategori sudah digunakan (selain kategori yang sedang diedit)
                $stmt = $pdo->prepare("SELECT id FROM kategori_buku WHERE nama_kategori = ? AND id != ?");
                $stmt->execute([$name, $id]);
                
                if ($stmt->rowCount() > 0) {
                    $message = 'Kategori dengan nama tersebut sudah ada';
                    $messageType = 'danger';
                } else {
                    $stmt = $pdo->prepare("UPDATE kategori_buku SET nama_kategori = ? WHERE id = ?");
                    if ($stmt->execute([$name, $id])) {
                        $message = 'Kategori berhasil diperbarui';
                        $messageType = 'success';
                    } else {
                        $message = 'Gagal memperbarui kategori';
                        $messageType = 'danger';
                    }
                }
            }
        }
    }
}

// Proses delete kategori jika ada parameter di URL
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Cek apakah kategori sedang digunakan pada tabel buku
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM buku WHERE id_kategori = ?");
    $stmt->execute([$id]);
    $bookCount = $stmt->fetchColumn();
    
    if ($bookCount > 0) {
        $message = 'Kategori tidak dapat dihapus karena masih digunakan oleh '.$bookCount.' buku';
        $messageType = 'danger';
    } else {
        $stmt = $pdo->prepare("DELETE FROM kategori_buku WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'Kategori berhasil dihapus';
            $messageType = 'success';
        } else {
            $message = 'Gagal menghapus kategori';
            $messageType = 'danger';
        }
    }
}

// Ambil data kategori untuk ditampilkan
$stmt = $pdo->query("SELECT * FROM kategori_buku ORDER BY nama_kategori ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data kategori untuk di-edit (jika ada)
$editCategory = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM kategori_buku WHERE id = ?");
    $stmt->execute([$id]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori Buku - <?php echo APP_NAME; ?></title>
    <!-- Bootstrap CSS (Offline) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <!-- Font Awesome (Offline) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <?php include '../includes/header.php'; ?>
            
            <div class="container-fluid">
                <h1 class="mt-4 mb-4">Manajemen Kategori Buku</h1>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><?php echo $editCategory ? 'Edit Kategori' : 'Tambah Kategori Baru'; ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="<?php echo $editCategory ? 'edit' : 'add'; ?>">
                                    <?php if ($editCategory): ?>
                                    <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="nama_kategori" class="form-label">Nama Kategori</label>
                                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" 
                                               value="<?php echo $editCategory ? htmlspecialchars($editCategory['nama_kategori']) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <?php echo $editCategory ? 'Perbarui' : 'Tambahkan'; ?> Kategori
                                        </button>
                                    </div>
                                    
                                    <?php if ($editCategory): ?>
                                    <div class="d-grid mt-2">
                                        <a href="categories.php" class="btn btn-secondary">Batal Edit</a>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Daftar Kategori</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($categories) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">No</th>
                                                <th>Nama Kategori</th>
                                                <th style="width: 20%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $index => $category): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($category['nama_kategori']); ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    Belum ada kategori buku yang ditambahkan.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle (Offline) -->
    <script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
