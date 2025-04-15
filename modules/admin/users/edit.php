 <!-- File: modules/admin/users/edit.php -->
<?php
require_once '../../../config/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('/');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$database = new Database();
$conn = $database->getConnection();

// Ambil data user
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    setMessage('danger', 'Pengguna tidak ditemukan!');
    redirect('/modules/admin/users/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $role = sanitize($_POST['role']);
    $no_telepon = sanitize($_POST['no_telepon']);
    $alamat = sanitize($_POST['alamat']);

    // Cek username dan email yang sudah ada (kecuali untuk user yang sedang diedit)
    $check_query = "SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$username, $email, $id]);
    
    if ($check_stmt->fetchColumn() > 0) {
        setMessage('danger', 'Username atau email sudah digunakan!');
    } else {
        $query = "UPDATE users SET 
                  username = ?, 
                  email = ?, 
                  nama_lengkap = ?, 
                  role = ?, 
                  no_telepon = ?, 
                  alamat = ?, 
                  updated_at = NOW()";
        
        $params = [$username, $email, $nama_lengkap, $role, $no_telepon, $alamat];
        
        // Jika password diisi, update password
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query .= ", password = ?";
            $params[] = $password;
        }
        
        $query .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute($params)) {
            setMessage('success', 'Data pengguna berhasil diupdate!');
            redirect('/modules/admin/users/index.php');
        } else {
            setMessage('danger', 'Gagal mengupdate data pengguna!');
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
                    <h3 class="card-title">Edit Pengguna</h3>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form
                                    -label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Kosongkan jika tidak ingin mengubah password">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                </div>
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                           value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="petugas" <?php echo $user['role'] === 'petugas' ? 'selected' : ''; ?>>Petugas</option>
                                        <option value="peminjam" <?php echo $user['role'] === 'peminjam' ? 'selected' : ''; ?>>Peminjam</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="no_telepon" class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" 
                                           value="<?php echo htmlspecialchars($user['no_telepon']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?php echo htmlspecialchars($user['alamat']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../../../includes/footer.php'); ?>
