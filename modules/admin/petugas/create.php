<?php
require_once '../../../config/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $no_telepon = sanitize($_POST['no_telepon']);
    $alamat = sanitize($_POST['alamat']);

    $database = new Database();
    $conn = $database->getConnection();

    // Cek username dan email yang sudah ada
    $check_query = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$username, $email]);
    
    if ($check_stmt->fetchColumn() > 0) {
        setMessage('danger', 'Username atau email sudah digunakan!');
    } else {
        // Set role sebagai petugas
        $query = "INSERT INTO users (username, email, password, nama_lengkap, role, no_telepon, alamat, created_at) 
                  VALUES (?, ?, ?, ?, 'petugas', ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute([$username, $email, $password, $nama_lengkap, $no_telepon, $alamat])) {
            setMessage('success', 'Petugas berhasil ditambahkan!');
            redirect('/modules/admin/petugas/index.php');
        } else {
            setMessage('danger', 'Gagal menambahkan petugas!');
        }
    }
}
?>

<?php include('../../../includes/header.php'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tambah Petugas Baru</h3>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                                </div>
                                <div class="mb-3">
                                    <label for="no_telepon" class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" required>
                                </div>
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../../../includes/footer.php'); ?>
