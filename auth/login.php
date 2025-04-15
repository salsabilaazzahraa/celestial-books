<?php
require_once '../config/constants.php';
require_once '../config/functions.php';

if (isset($_POST['login'])) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT * FROM users WHERE username = :username OR email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            setMessage('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');

            switch ($user['role']) {
                case 'admin':
                    redirect('/modules/admin/dashboard.php');
                    break;
                case 'petugas':
                    redirect('/modules/petugas/dashboard.php');
                    break;
                case 'peminjam':
                    redirect('/modules/peminjam/dashboard.php');
                    break;
            }
        } else {
            setMessage('danger', 'Password yang Anda masukkan salah!');
        }
    } else {
        setMessage('danger', 'Username/Email tidak ditemukan!');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
    <style>
        .auth-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #3498db 0%, #85c1e9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .floating-books {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .book-animation {
            position: absolute;
            animation: floatBook 6s infinite;
            opacity: 0.6;
            width: 60px;
            height: auto;
        }

        .book-1 { top: 15%; left: 10%; animation-delay: 0s; }
        .book-2 { top: 40%; left: 5%; animation-delay: 1s; }
        .book-3 { top: 70%; left: 12%; animation-delay: 2s; }
        .book-4 { top: 25%; right: 10%; animation-delay: 1.5s; }
        .book-5 { top: 55%; right: 8%; animation-delay: 2.5s; }
        .book-6 { top: 75%; right: 12%; animation-delay: 3s; }

        @keyframes floatBook {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 380px;
            position: relative;
            z-index: 2;
            transition: transform 0.3s;
        }

        .auth-card:hover {
            transform: translateY(-5px);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .auth-header img {
            max-width: 100px;
            margin-bottom: 10px;
        }

        .form-floating {
            margin-bottom: 12px;
        }

        .form-floating .form-control {
            height: calc(3rem + 2px);
            padding: 1rem 0.75rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .form-floating label {
            padding: 0.75rem 0.75rem;
        }

        .login-button {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            background: #3498db;
            border: none;
            color: white;
            font-weight: bold;
            margin-top: 12px;
            transition: all 0.3s ease;
        }

        .login-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .auth-links {
            text-align: center;
            margin-top: 15px;
            font-size: 0.85rem;
        }

        .auth-links a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-links a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .app-title {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            font-family: 'Poppins', sans-serif;
        }

        /* Animasi untuk logo */
        .logo-container {
            display: inline-block;
            position: relative;
        }

        .logo-container img {
            animation: pulse 3s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Animasi background */
        .animated-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            background: linear-gradient(135deg, #3498db 0%, #85c1e9 100%);
            overflow: hidden;
        }
        
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: moveBg 15s infinite alternate;
        }
        
        .bg-circle-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .bg-circle-2 {
            width: 200px;
            height: 200px;
            top: 60%;
            left: 20%;
            animation-delay: 3s;
        }
        
        .bg-circle-3 {
            width: 350px;
            height: 350px;
            top: 30%;
            right: 10%;
            animation-delay: 6s;
        }
        
        .bg-circle-4 {
            width: 250px;
            height: 250px;
            bottom: 10%;
            right: 20%;
            animation-delay: 9s;
        }
        
        @keyframes moveBg {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 30px); }
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <!-- Animasi Background -->
        <div class="animated-bg">
            <div class="bg-circle bg-circle-1"></div>
            <div class="bg-circle bg-circle-2"></div>
            <div class="bg-circle bg-circle-3"></div>
            <div class="bg-circle bg-circle-4"></div>
        </div>
        
        <!-- Animasi Buku Melayang -->
        <div class="floating-books">
            <img src="<?php echo IMG_PATH; ?>/book1.png" alt="Book 1" class="book-animation book-1">
            <img src="<?php echo IMG_PATH; ?>/book2.png" alt="Book 2" class="book-animation book-2">
            <img src="<?php echo IMG_PATH; ?>/book3.png" alt="Book 3" class="book-animation book-3">
            <img src="<?php echo IMG_PATH; ?>/book4.png" alt="Book 4" class="book-animation book-4">
            <img src="<?php echo IMG_PATH; ?>/book5.png" alt="Book 5" class="book-animation book-5">
            <img src="<?php echo IMG_PATH; ?>/book6.png" alt="Book 6" class="book-animation book-6">
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-container">
                    <img src="<?php echo IMG_PATH; ?>/logo.png" alt="Logo">
                </div>
                <h1 class="app-title"><?php echo APP_NAME; ?></h1>
                <!-- Baris teks berikut dihapus -->
                <!-- <p class="app-description"><?php echo APP_DESCRIPTION; ?></p> -->
                <h2 class="fs-5">Masuk ke Akun Anda</h2>
                <p class="text-muted small">Selamat datang kembali! Silakan masuk ke akun Anda.</p>
            </div>

            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show">
                    <?php echo $_SESSION['alert']['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username atau Email" required>
                    <label for="username">Username atau Email</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>

                <button type="submit" name="login" class="login-button">Masuk</button>

                <div class="auth-links">
                    <p>Belum punya akun? <a href="register.php">Daftar Sekarang</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo JS_PATH; ?>/bootstrap.bundle.min.js"></script>
</body>
</html>