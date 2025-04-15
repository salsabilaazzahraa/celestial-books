<?php
require_once '../../../config/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('/');
}

// Ambil data petugas saja
$database = new Database();
$conn = $database->getConnection();

$query = "SELECT * FROM users WHERE role = 'petugas' ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$petugas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include('../../../includes/header.php'); ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-4">Manajemen Petugas</h2>

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

            <!-- Statistics Card - Dirapikan -->
            <div class="card mb-4">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="icon-shape bg-gradient-success shadow text-center border-radius-md">
                                <i class="fas fa-user-tie text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="col">
                            <p class="text-sm mb-0 text-uppercase font-weight-bold text-success">Total Petugas</p>
                            <h4 class="font-weight-bolder mb-0"><?php echo count($petugas); ?></h4>
                        </div>
                        <div class="col-auto">
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Petugas Baru
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                            <th>No. Telepon</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($petugas as $p): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($p['username']); ?></td>
                                <td><?php echo htmlspecialchars($p['email']); ?></td>
                                <td><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($p['no_telepon'] ?? '-'); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $p['id']; ?>)">
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
        width: 42px;
        height: 42px;
        background-position: center;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bg-gradient-success {
        background: linear-gradient(310deg, #17ad37, #98ec2d);
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
        transform: translateY(-2px);
        box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.1);
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
        if (confirm('Apakah Anda yakin ingin menghapus petugas ini?')) {
            window.location.href = 'delete.php?id=' + userId;
        }
    }
</script>

<?php include('../../../includes/footer.php'); ?>