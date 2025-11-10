<?php 
include "config/db.php"; 
session_start();  

// Handle Register
if (isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nim = $_POST['nim'];
    $universitas = $_POST['universitas'];
    $jurusan = $_POST['jurusan'];
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $foto = $_FILES['foto'];

    // Validasi
    if ($password !== $confirm_password) {
        $register_error = "Password dan konfirmasi password tidak cocok.";
    } else {
        // Cek email sudah ada
        $check_email = mysqli_prepare($conn, "SELECT * FROM register WHERE email = ?");
        mysqli_stmt_bind_param($check_email, "s", $email);
        mysqli_stmt_execute($check_email);
        if (mysqli_stmt_get_result($check_email)->num_rows > 0) {
            $register_error = "Email sudah terdaftar.";
        } else {
            // Cek NIM sudah ada
            $check_nim = mysqli_prepare($conn, "SELECT * FROM register WHERE nim = ?");
            mysqli_stmt_bind_param($check_nim, "s", $nim);
            mysqli_stmt_execute($check_nim);
            if (mysqli_stmt_get_result($check_nim)->num_rows > 0) {
                $register_error = "NIM sudah terdaftar.";
            } else {
                $today = date('Y-m-d');
                if ($tanggal_masuk < $today) {
                    $register_error = "Tanggal masuk tidak boleh kurang dari hari ini.";
                } elseif ($tanggal_keluar <= $tanggal_masuk) {
                    $register_error = "Tanggal keluar harus lebih besar dari tanggal masuk.";
                } else {
                    // Validasi file foto
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    if (!in_array($foto['type'], $allowed_types)) {
                        $register_error = "Format file foto harus JPG atau PNG.";
                    } elseif ($foto['size'] > $max_size) {
                        $register_error = "Ukuran file foto maksimal 2MB.";
                    } else {
                        // Upload file
                        $foto_name = uniqid() . '_' . $foto['name'];
                        $foto_path = "uploads/" . $foto_name;
                        if (!move_uploaded_file($foto['tmp_name'], $foto_path)) {
                            $register_error = "Gagal mengunggah foto.";
                        } else {
                            // Hash password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert ke tabel register dengan prepared statement
                            $query = "INSERT INTO register (nama, email, password, nim, universitas, jurusan, no_hp, alamat, tanggal_masuk, tanggal_keluar, foto, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                            $stmt = mysqli_prepare($conn, $query);
                            mysqli_stmt_bind_param($stmt, "sssssssssss", $nama, $email, $hashed_password, $nim, $universitas, $jurusan, $no_hp, $alamat, $tanggal_masuk, $tanggal_keluar, $foto_path);
                            if (mysqli_stmt_execute($stmt)) {
                                $register_success = "Pendaftaran berhasil! Menunggu persetujuan admin.";
                            } else {
                                $register_error = "Terjadi kesalahan saat pendaftaran.";
                                // Hapus file jika gagal insert
                                if (file_exists($foto_path)) {
                                    unlink($foto_path);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - SPAM DPUPR Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-gradient: linear-gradient(135deg, #2563eb, #1d4ed8);
            --accent: #1d4ed8;
            --success: #10b981;
            --danger: #ef4444;
            --text: #1a1a1a;
            --text-light: #666;
            --bg: #f8fafc;
            --card-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            --border: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: 
                linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                url('images/img4.webp') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .container-wrapper {
            max-width: 1400px;
            width: 100%;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            position: relative;
            z-index: 1;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            min-height: 85vh;
        }

        .left-panel {
            padding: 40px 50px;
            background: white;
            overflow-y: auto;
            max-height: 85vh;
        }

        /* Custom Scrollbar */
        .left-panel::-webkit-scrollbar {
            width: 8px;
        }

        .left-panel::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .left-panel::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .header {
            margin-bottom: 30px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo img {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }

        .account-link {
            color: var(--text-light);
            font-size: 14px;
            text-decoration: none;
            transition: color 0.3s;
        }

        .account-link:hover {
            color: var(--primary);
        }

        .account-link span {
            color: var(--primary);
            font-weight: 600;
        }

        h1 {
            font-size: 32px;
            color: var(--text);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .subtitle {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 15px;
        }

        .divider {
            width: 50px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 2px;
            margin-bottom: 20px;
        }

        /* Form Grid - 2 Columns */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            color: var(--text);
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 500;
        }

        label i {
            color: var(--primary);
            margin-right: 5px;
            font-size: 12px;
        }

        .input-wrapper {
            position: relative;
        }

        input, textarea, select {
            width: 100%;
            padding: 11px 35px 11px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: #fafafa;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        textarea {
            min-height: 80px;
            resize: vertical;
            padding-right: 12px;
        }

        .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            font-size: 14px;
        }

        textarea ~ .input-icon {
            top: 20px;
            transform: none;
        }

        .file-input {
            padding: 15px;
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input:hover {
            background: #e2e8f0;
            border-color: var(--primary);
        }

        .file-input input[type="file"] {
            display: none;
        }

        .file-input p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
            margin-top: 20px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.4);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            grid-column: 1 / -1;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .footer-text {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        /* Right Panel */
        .right-panel {
            background: var(--primary-gradient);
            padding: 40px 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: white;
        }

        .right-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            backdrop-filter: blur(10px);
        }

        .illustration {
            position: relative;
            z-index: 1;
        }

        .card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: float 3s ease-in-out infinite;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .card:nth-child(2) { animation-delay: 0.5s; }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        .icon-circle {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .card-content {
            font-size: 14px;
            line-height: 1.6;
            opacity: 0.9;
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr 350px;
            }
            .left-panel {
                padding: 35px 40px;
            }
            .right-panel {
                padding: 35px 25px;
            }
        }

        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .right-panel {
                display: none;
            }
            .left-panel {
                padding: 30px 25px;
                max-height: none;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 26px;
            }
            .left-panel {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="content-grid">
            <!-- Left Panel: Form -->
            <div class="left-panel">
                <div class="header">
                    <div class="header-top">
                        <div class="logo">
                            <img src="images/LogoBanten.png" alt="Logo">
                        </div>
                        <a href="login.php" class="account-link">Sudah punya akun? <span>Login</span></a>
                    </div>
                    <h1>Registrasi SPAM</h1>
                    <p class="subtitle">Sistem Pengelolaan Administrasi Magang DPUPR Banten</p>
                    <div class="divider"></div>
                </div>

                <form method="post" id="registerForm" enctype="multipart/form-data">
                    <div class="form-grid">
                        <?php if(isset($register_error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= $register_error ?>
                            </div>
                        <?php endif; ?>

                        <?php if(isset($register_success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?= $register_success ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group full-width">
                            <label><i class="fas fa-user"></i> Nama Lengkap</label>
                            <div class="input-wrapper">
                                <input type="text" name="nama" placeholder="Masukkan nama lengkap" required>
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <div class="input-wrapper">
                                <input type="email" name="email" placeholder="Masukkan email Anda" required>
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> NIM/NISN</label>
                            <div class="input-wrapper">
                                <input type="text" name="nim" placeholder="Masukkan NIM/NISN" required>
                                <i class="fas fa-id-card input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-university"></i> Universitas/Sekolah</label>
                            <div class="input-wrapper">
                                <input type="text" name="universitas" placeholder="Nama universitas/sekolah" required>
                                <i class="fas fa-university input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-graduation-cap"></i> Jurusan</label>
                            <div class="input-wrapper">
                                <input type="text" name="jurusan" placeholder="Jurusan/program studi" required>
                                <i class="fas fa-graduation-cap input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> No. HP</label>
                            <div class="input-wrapper">
                                <input type="tel" name="no_hp" placeholder="Nomor HP" required>
                                <i class="fas fa-phone input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> Tanggal Masuk</label>
                            <div class="input-wrapper">
                                <input type="date" name="tanggal_masuk" required>
                                <i class="fas fa-calendar-check input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-calendar-times"></i> Tanggal Keluar</label>
                            <div class="input-wrapper">
                                <input type="date" name="tanggal_keluar" required>
                                <i class="fas fa-calendar-times input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                            <div class="input-wrapper">
                                <textarea name="alamat" rows="2" placeholder="Masukkan alamat lengkap" required></textarea>
                                <i class="fas fa-map-marker-alt input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-camera"></i> Foto Pas Foto (4x6 atau 1x1)</label>
                            <div class="file-input" onclick="document.querySelector('input[type=file]').click()">
                                <input type="file" name="foto" accept="image/jpeg,image/png,image/jpg" required onchange="updateFileName(this)">
                                <p id="file-label">
                                    <i class="fas fa-cloud-upload-alt"></i> Klik untuk upload foto (JPG/PNG, maks 2MB)
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Password</label>
                            <div class="input-wrapper">
                                <input type="password" name="password" id="password" placeholder="Masukkan password" required>
                                <span class="input-icon" onclick="togglePassword('password', this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Konfirmasi Password</label>
                            <div class="input-wrapper">
                                <input type="password" name="confirm_password" id="confirmPassword" placeholder="Konfirmasi password" required>
                                <span class="input-icon" onclick="togglePassword('confirmPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="register" class="btn-register">
                                <i class="fas fa-user-plus me-2"></i> Daftar Sekarang
                            </button>
                        </div>
                    </div>
                </form>

                <div class="footer-text">
                    © 2025 Sistem Pengelolaan Administrasi Magang DPUPR Banten
                </div>
            </div>

            <!-- Right Panel: Illustration -->
            <div class="right-panel">
                <div class="illustration">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Magang Terjadwal</div>
                            <div class="icon-circle"><i class="fas fa-calendar-alt"></i></div>
                        </div>
                        <div class="card-content">
                            Kelola jadwal magang Anda dengan mudah dan terstruktur.
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Persetujuan Cepat</div>
                            <div class="icon-circle"><i class="fas fa-check-double"></i></div>
                        </div>
                        <div class="card-content">
                            Admin akan memproses pendaftaran Anda dalam waktu singkat.
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Data Aman</div>
                            <div class="icon-circle"><i class="fas fa-shield-alt"></i></div>
                        </div>
                        <div class="card-content">
                            Sistem keamanan terjamin untuk melindungi data pribadi Anda.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconEl = icon.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                iconEl.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                iconEl.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function updateFileName(input) {
            const label = document.getElementById('file-label');
            if (input.files && input.files[0]) {
                const fileName = input.files[0].name;
                label.innerHTML = `<i class="fas fa-check-circle"></i> ${fileName}`;
            }
        }

        // Set min date
        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.min = today;
            });

            // Auto-hide alerts
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'all 0.3s';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>