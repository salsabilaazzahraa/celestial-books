<?php
require_once '../../../config/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('/');
}

// Ambil data users
$database = new Database();
$conn = $database->getConnection();

$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung jumlah untuk setiap role
$admin_count = 0;
$petugas_count = 0;
$peminjam_count = 0;

foreach ($users as $user) {
    switch ($user['role']) {
        case 'admin':
            $admin_count++;
            break;
        case 'petugas':
            $petugas_count++;
            break;
        case 'peminjam':
            $peminjam_count++;
            break;
    }
}

?>

<?php include('../../../includes/header.php'); ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-4">Manajemen Pengguna</h2>

            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']); 
                unset($_SESSION['message_type']); 
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row">
                <!-- Card Total Admin -->
                <div class="col-xl-4 col-sm-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold text-primary">Total Admin</p>
                                        <h5 class="font-weight-bolder mb-0">
                                            <?php echo $admin_count; ?>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                        <i class="fas fa-user-shield text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Total Petugas -->
                <div class="col-xl-4 col-sm-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold text-success">Total Petugas</p>
                                        <h5 class="font-weight-bolder mb-0">
                                            <?php echo $petugas_count; ?>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                                        <i class="fas fa-user-tie text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Total Peminjam -->
                <div class="col-xl-4 col-sm-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold text-info">Total Peminjam</p>
                                        <h5 class="font-weight-bolder mb-0">
                                            <?php echo $peminjam_count; ?>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                                        <i class="fas fa-users text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Pengguna Baru
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Nama Lengkap</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                                <td>
                                    <span class="badge <?php
                                                    echo $user['role'] === 'admin' ? 'bg-primary' : ($user['role'] === 'petugas' ? 'bg-success' : 'bg-info');
                                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .icon-shape {
        width: 48px;
        height: 48px;
        background-position: center;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bg-gradient-primary {
        background: linear-gradient(310deg, #2152ff, #21d4fd);
    }

    .bg-gradient-success {
        background: linear-gradient(310deg, #17ad37, #98ec2d);
    }

    .bg-gradient-info {
        background: linear-gradient(310deg, #2152ff, #21d4fd);
    }

    .border-radius-md {
        border-radius: 0.5rem;
    }

    .icon i {
        color: white;
    }

    .card {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
        border: none;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .numbers p {
        font-size: 14px;
        margin-bottom: 0;
        font-weight: 600;
    }

    .numbers h5 {
        font-size: 28px;
        margin-top: 5px;
        margin-bottom: 0;
    }

    .text-lg {
        font-size: 1.25rem;
    }

    .opacity-10 {
        opacity: 1;
    }
</style>

<script>
    function confirmDelete(userId) {
        if (confirm('Apakah Anda yakin ingin menghapus pengguna ini?')) {
            window.location.href = 'delete.php?id=' + userId;
        }
    }
</script>

<?php include('../../../includes/footer.php'); ?>