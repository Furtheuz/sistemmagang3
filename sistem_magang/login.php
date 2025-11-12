<?php
include "config/db.php";
session_start();

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email dan password harus diisi!";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, nama, email, password, role FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'nama' => $user['nama'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Email tidak ditemukan!";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!-- HTML login tetap sama -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SPAM DPUPR Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS login tetap sama seperti sebelumnya */
        :root {
            --primary: #2563eb;
            --primary-gradient: linear-gradient(135deg, #2563eb, #1d4ed8);
            --danger: #ef4444;
            --card-shadow: 0 20px 60px rgba(0,0,0,0.15);
            --border: #e0e0e0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('images/img4.webp') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow: hidden;
        }
        .container-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            max-height: 95vh;
        }
        .left-panel { padding: 50px 45px; background: white; display: flex; flex-direction: column; justify-content: center; overflow-y: auto; }
        .logo { width: 70px; height: 70px; margin: 0 auto 20px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .logo img { width: 60px; height: 60px; object-fit: contain; }
        h1 { font-size: 36px; font-weight: 700; text-align: center; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; font-size: 15px; }
        .divider { width: 60px; height: 4px; background: var(--primary-gradient); border-radius: 2px; margin: 25px auto; }
        .form-group { margin-bottom: 22px; position: relative; }
        label { font-size: 14px; font-weight: 500; margin-bottom: 8px; display: block; }
        label i { color: var(--primary); margin-right: 6px; }
        input { width: 100%; padding: 15px 45px 15px 16px; border: 1px solid var(--border); border-radius: 12px; font-size: 15px; background: #fafafa; transition: all 0.3s; }
        input:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .input-icon { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #999; cursor: pointer; font-size: 16px; }
        .btn-login { width: 100%; padding: 16px; background: var(--primary-gradient); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 8px 20px rgba(37,99,235,0.3); }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(37,99,235,0.4); }
        .alert-danger { background: #fee2e2; color: #991b1b; padding: 14px 18px; border-radius: 12px; border: 1px solid #fecaca; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; animation: shake 0.5s ease; }
        @keyframes shake { 0%,100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
        .register-link { text-align: center; margin-top: 30px; padding-top: 25px; border-top: 1px solid #e5e7eb; }
        .register-link a { color: var(--primary); font-weight: 600; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
        .right-panel { background: var(--primary-gradient); padding: 60px 50px; color: white; position: relative; overflow: hidden; }
        .right-panel::before, .right-panel::after { content: ''; position: absolute; background: rgba(255,255,255,0.1); border-radius: 50%; backdrop-filter: blur(10px); }
        .right-panel::before { top: -50%; right: -20%; width: 450px; height: 450px; }
        .right-panel::after { bottom: -30%; left: -15%; width: 350px; height: 350px; }
        .card { background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 20px; padding: 25px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.2); animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .card:nth-child(2) { animation-delay: 0.5s; }
        @media (max-width: 968px) { .container-wrapper { grid-template-columns: 1fr; max-width: 500px; max-height: none; } .right-panel { display: none; } .left-panel { padding: 40px 35px; } }
        @media (max-width: 576px) { .left-panel { padding: 35px 25px; } h1 { font-size: 28px; } }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="left-panel">
            <div class="text-center mb-4">
                <div class="logo">
                    <img src="images/LogoBanten.png" alt="Logo">
                </div>
                <h1>Selamat Datang</h1>
                <p class="subtitle">Sistem Pengelolaan Administrasi Magang<br>DPUPR Banten</p>
                <div class="divider"></div>
            </div>

            <?php if(!empty($error)): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong><?= htmlspecialchars($error) ?></strong>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <div class="position-relative">
                        <input type="email" name="email" placeholder="Masukkan email Anda" required autofocus>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="position-relative">
                        <input type="password" name="password" id="loginPassword" placeholder="Masukkan password Anda" required>
                        <span class="input-icon" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Masuk ke Dashboard
                </button>
            </form>

            <div class="register-link">
                <p>Belum punya akun? <a href="register.php">Daftar Sekarang</a></p>
            </div>

            <div class="text-center text-muted small mt-4">
                © 2025 SPAM DPUPR Banten - All Rights Reserved
            </div>
        </div>

        <div class="right-panel">
            <div class="welcome-text">
                <h2>Kelola Magang<br>dengan Mudah</h2>
                <p>Sistem terintegrasi untuk memudahkan administrasi dan monitoring kegiatan magang</p>
            </div>
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title">Akses Cepat</div>
                    <div class="icon-circle"><i class="fas fa-bolt"></i></div>
                </div>
                <div class="card-content">Login sekali dan akses semua fitur dashboard</div>
            </div>
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title">Keamanan Terjamin</div>
                    <div class="icon-circle"><i class="fas fa-shield-alt"></i></div>
                </div>
                <div class="card-content">Data dilindungi enkripsi berlapis</div>
            </div>
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title">Monitoring Real-time</div>
                    <div class="icon-circle"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="card-content">Pantau progress magang secara langsung</div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('loginPassword');
            const icon = document.querySelector('.input-icon i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        setTimeout(() => {
            const alert = document.querySelector('.alert-danger');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    </script>
</body>
</html>