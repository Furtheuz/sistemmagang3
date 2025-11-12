<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['id'];
$userName = $user['nama'] ?? 'User';
$pesan = '';

// Inisialisasi $peserta_data sebagai null
$peserta_data = null;

// Role-based styling
$roleIcons = [
    'admin' => '👑',
    'pembimbing' => '👨‍🏫',
    'user' => ''
];

// Handle password change
if (isset($_POST['ganti_pass'])) {
    $pw_lama = $_POST['pw_lama'];
    $pw_baru = $_POST['pw_baru'];
    $pw_konfirmasi = $_POST['pw_konfirmasi'];
    
    if ($pw_baru !== $pw_konfirmasi) {
        $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Konfirmasi password tidak cocok.</div>';
    } elseif (strlen($pw_baru) < 6) {
        $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Password baru minimal 6 karakter.</div>';
    } else {
        $match = password_verify($pw_lama, $user['password']) || md5($pw_lama) === $user['password'];
        if ($match) {
            $hash_baru = password_hash($pw_baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash_baru, $user['id']);
            if ($stmt->execute()) {
                $_SESSION['user']['password'] = $hash_baru;
                $pesan = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Password berhasil diganti.</div>';
            } else {
                $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal mengganti password.</div>';
            }
            $stmt->close();
        } else {
            $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Password lama salah.</div>';
        }
    }
}

// Get additional user data if exists
if ($role === 'user') {
    $stmt = $conn->prepare("SELECT p.*, i.nama as institusi_nama FROM peserta p LEFT JOIN institusi i ON p.institusi_id = i.id WHERE p.user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $peserta_data = $res->fetch_assoc();
    $stmt->close();
}

// Handle profile photo upload (for users only)
if (isset($_POST['upload_foto']) && $role === 'user') {
    $target_dir = "Uploads/";
    $allowed_types = ['jpg', 'jpeg', 'png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!empty($_FILES['foto']['name'])) {
        $file = $_FILES['foto'];
        $file_name = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $file['size'];
        $new_file_name = "profile_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;

        // Validate file
        if (!in_array($file_ext, $allowed_types)) {
            $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Format file tidak didukung. Gunakan JPG atau PNG.</div>';
        } elseif ($file_size > $max_size) {
            $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Ukuran file terlalu besar. Maksimal 2MB.</div>';
        } else {
            // Delete old photo if exists and valid
            if ($peserta_data && !empty($peserta_data['foto']) && file_exists($peserta_data['foto'])) {
                unlink($peserta_data['foto']);
            }
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("UPDATE peserta SET foto=? WHERE user_id=?");
                $stmt->bind_param("si", $target_file, $user_id);
                if ($stmt->execute()) {
                    $pesan = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Foto profil berhasil diperbarui.</div>';
                    // Refresh peserta_data
                    $stmt = $conn->prepare("SELECT p.*, i.nama as institusi_nama FROM peserta p LEFT JOIN institusi i ON p.institusi_id = i.id WHERE p.user_id = ? LIMIT 1");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $peserta_data = $res->fetch_assoc();
                    $stmt->close();
                } else {
                    $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal memperbarui foto di database.</div>';
                }
            } else {
                $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal mengunggah foto.</div>';
            }
        }
    } else {
        $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Pilih file untuk diunggah.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - <?= ucfirst($role) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1E3A8A;
            --secondary-color: #EFF6FF;
            --accent-color: #60A5FA;
            --text-color: #1f2937;
            --muted-color: #6b7280;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #ffffff 100%);
            min-height: 100vh;
            color: var(--text-color);
            overflow-x: hidden;
            margin: 0;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .hamburger {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.5rem;
            transition: background 0.3s ease;
            display: block;
        }

        .hamburger:hover {
            background: #2563EB;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary-color) 0%, #2563EB 100%);
            color: white;
            padding: 0;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            transition: transform 0.3s ease;
            z-index: 999;
            transform: translateX(0);
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar:not(.hidden) {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="pattern" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M0 10h20M10 0v20" stroke="%2360A5FA" stroke-width="1" opacity="0.2"/></pattern></defs><rect width="100" height="100" fill="url(%23pattern)"/></svg>');
            opacity: 0.1;
            pointer-events: none;
        }

        .user-profile {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            border: 2px solid var(--accent-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(96,165,250,0.5);
        }

        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .user-role {
            font-size: 0.875rem;
            background: var(--accent-color);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: inline-block;
        }

        .nav-menu {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            text-decoration: none;
            padding: 0.875rem 1.25rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(96,165,250,0.2);
            color: white !important;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: rgba(96,165,250,0.3);
            color: white !important;
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }

        .logout-link .nav-link {
            color: #fecaca !important;
            background: rgba(239, 68, 68, 0.1);
        }

        .logout-link .nav-link:hover {
            background: rgba(239, 68, 68, 0.2);
            color: white !important;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        .header-section {
            background: linear-gradient(45deg, var(--primary-color), #2563EB);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }

        .header-section h1 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .header-section p {
            font-size: 1rem;
            font-style: italic;
            opacity: 0.9;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #ffffff 100%);
        }

        .card-header h3 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }

        .alert-error {
            background: #fecaca;
            color: #991b1b;
            border-color: #ef4444;
        }

        .profile-card .card-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            border: 4px solid rgba(255,255,255,0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(255,255,255,0.5);
        }

        .profile-name {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            display: inline-block;
            font-weight: 500;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            width: 120px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: #1f2937;
            flex: 1;
        }

        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            background: #ef4444;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .id-card-section {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .id-card-section h4 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .id-card-guidelines {
            background: rgba(255,255,255,0.2);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.875rem;
        }

        .id-card-guidelines ul {
            padding-left: 1.5rem;
            margin: 0;
        }

        .content-container {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .content-container > div {
            flex: 1;
            min-width: 300px;
        }

        @media (max-width: 768px) {
            .header-section h1 {
                font-size: 1.5rem;
            }
            .header-section p {
                font-size: 0.9rem;
            }
            .content-container {
                flex-direction: column;
                gap: 1rem;
            }
            .profile-avatar {
                width: 120px;
                height: 120px;
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Hamburger Menu -->
        <button class="hamburger" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php if ($role === 'user' && $peserta_data && !empty($peserta_data['foto'])): ?>
                        <img src="<?= htmlspecialchars($peserta_data['foto']) ?>" alt="Profile Photo">
                    <?php else: ?>
                        <?= $roleIcons[$role] ?>
                    <?php endif; ?>
                </div>
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-role"><?= ucfirst($role) ?></div>
            </div>
            <div class="nav-menu">
                <?php
                $navItems = [
                    'admin' => [
                        ['dashboard.php', 'fas fa-home', 'Dashboard'],
                        ['peserta.php', 'fas fa-users', 'Data Peserta'],
                        ['schedule_report.php?tab=jadwal', 'fas fa-calendar-alt', 'Jadwal & Laporan'],
                        ['idcard.php', 'fas fa-id-card', 'Cetak ID Card'],
                        ['profile.php', 'fas fa-user', 'Profil']
                    ],
                    'pembimbing' => [
                        ['dashboard.php', 'fas fa-home', 'Dashboard'],
                        ['schedule_report.php?tab=jadwal', 'fas fa-calendar-check', 'Jadwal & Laporan'],
                        ['profile.php', 'fas fa-user', 'Profil']
                    ],
                    'user' => [
                        ['dashboard.php', 'fas fa-home', 'Dashboard'],
                        ['schedule_report.php?tab=jadwal', 'fas fa-calendar', 'Jadwal & Laporan'],
                        ['profile.php', 'fas fa-user', 'Profil']
                    ]
                ];
                foreach ($navItems[$role] as $item) {
                    list($href, $icon, $title) = $item;
                    $active = $href === 'profile.php' ? 'active' : '';
                    echo "<div class='nav-item'><a class='nav-link $active' href='$href'><i class='$icon'></i> $title</a></div>";
                }
                ?>
                <div class="nav-item logout-link"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header Section -->
            <div class="header-section">
                <h1><i class="fas fa-user-circle"></i> Profil Saya</h1>
                <p>Kelola informasi akun dan keamanan profil Anda</p>
            </div>

            <?= $pesan ?>

            <div class="content-container">
                <!-- Profile Card -->
                <div class="content-card profile-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Informasi Profil</h3>
                    </div>
                    <div class="card-body">
                        <div class="profile-avatar">
                            <?php if ($role === 'user' && $peserta_data && !empty($peserta_data['foto'])): ?>
                                <img src="<?= htmlspecialchars($peserta_data['foto']) ?>" alt="Profile Photo">
                            <?php else: ?>
                                <?= $roleIcons[$role] ?>
                            <?php endif; ?>
                        </div>
                        <div class="profile-name text-center"><?= htmlspecialchars($user['nama']) ?></div>
                        <div class="profile-role text-center"><?= ucfirst($role) ?></div>
                        <div class="mt-4">
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-user me-1"></i> Nama</div>
                                <div class="info-value"><?= htmlspecialchars($user['nama']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-envelope me-1"></i> Email</div>
                                <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-shield-alt me-1"></i> Role</div>
                                <div class="info-value"><?= ucfirst($user['role']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-check-circle text-success me-1"></i> Status</div>
                                <div class="info-value">Aktif</div>
                            </div>
                            <?php if ($peserta_data): ?>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-building me-1"></i> Institusi</div>
                                <div class="info-value"><?= htmlspecialchars($peserta_data['institusi_nama'] ?? 'Belum ditentukan') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-phone me-1"></i> Telepon</div>
                                <div class="info-value"><?= htmlspecialchars($peserta_data['telepon'] ?? 'Belum diisi') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-camera me-1"></i> Foto Profil</div>
                                <div class="info-value">
                                    <form method="post" enctype="multipart/form-data">
                                        <input type="file" name="foto" accept="image/jpeg,image/png" class="form-control mb-2" required>
                                        <button name="upload_foto" type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload"></i> Unggah Foto
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Password Form -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-lock"></i> Ganti Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" id="passwordForm">
                            <div class="form-group">
                                <label class="form-label">Password Lama</label>
                                <div class="input-group">
                                    <input type="password" name="pw_lama" class="form-control" placeholder="Masukkan password lama" required>
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" name="pw_baru" id="newPassword" class="form-control" placeholder="Masukkan password baru" required>
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="strengthBar"></div>
                                </div>
                                <div class="password-strength-text" id="strengthText"></div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Konfirmasi Password</label>
                                <div class="input-group">
                                    <input type="password" name="pw_konfirmasi" class="form-control" placeholder="Konfirmasi password baru" required>
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button name="ganti_pass" type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Password
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ID Card Section (Only for User) -->
            <?php if ($role === 'user' && $peserta_data): ?>
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-id-card"></i> ID Card Digital</h3>
                </div>
                <div class="card-body">
                    <p>Cetak kartu identitas digital Anda untuk keperluan magang</p>
                    <button class="btn btn-success" onclick="cetakIdCard(<?= $peserta_data['id'] ?>)">
                        <i class="fas fa-print"></i> Cetak ID Card
                    </button>
                    <div class="id-card-guidelines">
                        <p><strong>Persiapan Cetak ID Card:</strong></p>
                        <ul>
                            <li>Pastikan printer dalam kondisi baik dan memiliki tinta yang cukup.</li>
                            <li>Gunakan kertas foto glossy atau kertas kartu (cardstock) dengan ketebalan minimal 200 gsm untuk hasil terbaik.</li>
                            <li>Atur pengaturan printer ke kualitas tinggi (high quality) untuk cetakan yang jelas.</li>
                            <li>Siapkan gunting atau cutter untuk memotong kartu sesuai ukuran standar (86mm x 54mm).</li>
                            <li>Simpan file ID Card dalam format PDF untuk memastikan kompatibilitas.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('hidden');
            
            if (sidebar.classList.contains('hidden')) {
                mainContent.style.marginLeft = '0';
            } else {
                mainContent.style.marginLeft = window.innerWidth <= 768 ? '0' : '280px';
            }
        }

        // Toggle password visibility
        function togglePassword(button) {
            const inputGroup = button.parentElement;
            const input = inputGroup.querySelector('input');
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            let feedback = '';
            
            if (password.length >= 8) score += 1;
            if (password.length >= 12) score += 1;
            if (/[a-z]/.test(password)) score += 1;
            if (/[A-Z]/.test(password)) score += 1;
            if (/[0-9]/.test(password)) score += 1;
            if (/[^A-Za-z0-9]/.test(password)) score += 1;
            
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            if (score <= 2) {
                strengthBar.style.width = '33%';
                strengthBar.style.background = '#ef4444';
                feedback = 'Lemah';
            } else if (score <= 4) {
                strengthBar.style.width = '66%';
                strengthBar.style.background = '#f59e0b';
                feedback = 'Sedang';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.background = '#10b981';
                feedback = 'Kuat';
            }
            
            strengthText.textContent = feedback;
        }

        document.getElementById('newPassword').addEventListener('input', function(e) {
            checkPasswordStrength(e.target.value);
        });

        // File input validation
        document.querySelector('input[name="foto"]')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png'];
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan JPG atau PNG.');
                    e.target.value = '';
                } else if (file.size > maxSize) {
                    alert('Ukuran file terlalu besar. Maksimal 2MB.');
                    e.target.value = '';
                } else {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.querySelector('.profile-avatar img').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        // Form validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const oldPassword = document.querySelector('input[name="pw_lama"]').value;
            const newPassword = document.querySelector('input[name="pw_baru"]').value;
            const confirmPassword = document.querySelector('input[name="pw_konfirmasi"]').value;
            
            if (oldPassword === newPassword) {
                e.preventDefault();
                alert('Password baru harus berbeda dengan password lama!');
            } else if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
            } else if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password baru minimal 6 karakter!');
            }
        });

        // Cetak ID Card function
        function cetakIdCard(pesertaId) {
            window.open('priview_idcard.php?id=' + pesertaId, '_blank');
            fetch('log_cetak.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'peserta_id=' + pesertaId
            }).then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      alert('Riwayat cetak berhasil disimpan!');
                  } else {
                      alert('Gagal menyimpan riwayat cetak: ' + data.message);
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  alert('Terjadi kesalahan saat menyimpan riwayat cetak');
              });
        }

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Toggle password buttons
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                togglePassword(this);
            });
        });
    </script>
</body>
</html>