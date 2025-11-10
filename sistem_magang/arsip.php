<?php 
include "config/auth.php"; 
include "config/db.php"; 
checkLogin();

$role = $_SESSION['user']['role']; 
$userName = $_SESSION['user']['name'] ?? 'User';

// Role-based styling
$roleColors = [
    'admin' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'],
    'pembimbing' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'],
    'user' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8']
];

$roleIcons = [
    'admin' => '👑',
    'pembimbing' => '👨‍🏫',
    'user' => '👨‍🎓'
];

$currentTheme = ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'];

// Handle actions
$message = '';
$messageType = '';

// Arsipkan peserta
if (isset($_GET['arsipkan'])) {
    $peserta_id = (int)$_GET['arsipkan'];
    $keterangan = mysqli_real_escape_string($conn, $_GET['keterangan'] ?? 'Selesai');
    
    // Insert ke arsip
    $insertArsip = mysqli_query($conn, "INSERT INTO arsip(peserta_id, keterangan, tanggal_arsip) VALUES($peserta_id, '$keterangan', NOW())");
    
    // Update status peserta
    $updatePeserta = mysqli_query($conn, "UPDATE peserta SET status='selesai' WHERE id=$peserta_id");
    
    if ($insertArsip && $updatePeserta) {
        $message = 'Peserta berhasil diarsipkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengarsipkan peserta: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Restore peserta dari arsip
if (isset($_GET['restore'])) {
    $arsip_id = (int)$_GET['restore'];
    
    // Get peserta_id from arsip
    $arsipData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT peserta_id FROM arsip WHERE id = $arsip_id"));
    
    if ($arsipData) {
        $peserta_id = $arsipData['peserta_id'];
        
        // Delete from arsip
        $deleteArsip = mysqli_query($conn, "DELETE FROM arsip WHERE id = $arsip_id");
        
        // Update status peserta back to aktif
        $updatePeserta = mysqli_query($conn, "UPDATE peserta SET status='aktif' WHERE id = $peserta_id");
        
        if ($deleteArsip && $updatePeserta) {
            $message = 'Peserta berhasil dikembalikan dari arsip!';
            $messageType = 'success';
        } else {
            $message = 'Gagal mengembalikan peserta: ' . mysqli_error($conn);
            $messageType = 'error';
        }
    }
}

// Delete arsip permanently
if (isset($_GET['hapus'])) {
    $arsip_id = (int)$_GET['hapus'];
    
    if (mysqli_query($conn, "DELETE FROM arsip WHERE id = $arsip_id")) {
        $message = 'Data arsip berhasil dihapus!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menghapus data arsip: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// CRUD Institusi
if (isset($_POST['tambah_institusi'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_institusi']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat_institusi']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon_institusi']);
    
    $query = "INSERT INTO institusi (nama, alamat, telepon) VALUES ('$nama', '$alamat', '$telepon')";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Institusi berhasil ditambahkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menambahkan institusi: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

if (isset($_POST['update_institusi'])) {
    $id = (int)$_POST['institusi_id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_institusi']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat_institusi']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon_institusi']);
    
    $query = "UPDATE institusi SET nama='$nama', alamat='$alamat', telepon='$telepon' WHERE id=$id";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Institusi berhasil diupdate!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengupdate institusi: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

if (isset($_GET['hapus_institusi'])) {
    $id = (int)$_GET['hapus_institusi'];
    
    // Check if institusi is being used
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM peserta WHERE institusi_id = $id"));
    
    if ($check['count'] > 0) {
        $message = 'Institusi tidak dapat dihapus karena masih digunakan oleh peserta!';
        $messageType = 'error';
    } else {
        if (mysqli_query($conn, "DELETE FROM institusi WHERE id = $id")) {
            $message = 'Institusi berhasil dihapus!';
            $messageType = 'success';
        } else {
            $message = 'Gagal menghapus institusi: ' . mysqli_error($conn);
            $messageType = 'error';
        }
    }
}

// Get data
$arsip = mysqli_query($conn, "SELECT a.*, p.nama, p.email, i.nama as institusi 
                             FROM arsip a 
                             JOIN peserta p ON a.peserta_id = p.id 
                             LEFT JOIN institusi i ON p.institusi_id = i.id 
                             ORDER BY a.tanggal_arsip DESC");

$peserta_aktif = mysqli_query($conn, "SELECT * FROM peserta WHERE status='aktif' ORDER BY nama");

$institusi = mysqli_query($conn, "SELECT * FROM institusi ORDER BY nama");

// Get statistics
$total_arsip = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip"))['total'];
$arsip_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip WHERE MONTH(tanggal_arsip) = MONTH(NOW()) AND YEAR(tanggal_arsip) = YEAR(NOW())"))['total'];
$peserta_aktif_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status='aktif'"))['total'];
$total_institusi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM institusi"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Magang - <?= ucfirst($role) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?= $currentTheme['primary'] ?>;
            --secondary-color: <?= $currentTheme['secondary'] ?>;
            --accent-color: <?= $currentTheme['accent'] ?>;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #ffffff 100%);
            min-height: 100vh;
            color: #1f2937;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            padding: 0;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .user-profile {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 1;
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
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .user-role {
            font-size: 0.875rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: inline-block;
        }
        
        .nav-menu {
            padding: 1rem 0;
            position: relative;
            z-index: 1;
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
            position: relative;
            overflow: hidden;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white !important;
            transform: translateX(5px);
            backdrop-filter: blur(10px);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .logout-link {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 1rem;
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
            overflow-y: auto;
        }
        
        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .header h1 {
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header .subtitle {
            color: #6b7280;
            font-weight: 400;
            margin-top: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        .stat-title {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
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
            box-shadow: 0 0 0 3px rgba(var(--primary-color), 0.1);
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
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-info {
            background: #06b6d4;
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        
        .table tbody tr:hover {
            background: #f9fafb;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid;
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
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #ffffff 100%);
            border-radius: 16px 16px 0 0;
        }
        
        .modal-header h4 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .close {
            float: right;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            color: #6b7280;
        }
        
        .close:hover {
            color: var(--primary-color);
        }
        
        .tab-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .table-container {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="user-profile">
                <div class="user-avatar">
                    <?= $roleIcons[$role] ?>
                </div>
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-role"><?= ucfirst($role) ?></div>
            </div>

            <div class="nav-menu">
                <?php if ($role == 'admin'): ?>
                    <div class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></div>
                    <div class="nav-item"><a class="nav-link" href="peserta.php"><i class="fas fa-users"></i> Data Peserta</a></div>
                    <div class="nav-item"><a class="nav-link" href="jadwal.php"><i class="fas fa-calendar-alt"></i> Jadwal</a></div>
                    <div class="nav-item"><a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> Laporan</a></div>
                    <div class="nav-item"><a class="nav-link" href="idcard.php"><i class="fas fa-id-card"></i> Cetak ID Card</a></div>
                    <div class="nav-item"><a class="nav-link" href="arsip.php"><i class="fas fa-archive"></i> Arsip</a></div>
                    <div class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profil</a></div>
                <?php elseif ($role == 'pembimbing'): ?>
                    <div class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></div>
                    <div class="nav-item"><a class="nav-link" href="jadwal.php"><i class="fas fa-calendar-check"></i> Jadwal Peserta Bimbingan</a></div>
                    <div class="nav-item"><a class="nav-link" href="laporan.php"><i class="fas fa-clipboard-check"></i> Validasi Laporan</a></div>
                    <div class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profil</a></div>
                <?php elseif ($role == 'user'): ?>
                    <div class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></div>
                    <div class="nav-item"><a class="nav-link" href="jadwal.php"><i class="fas fa-calendar"></i> Jadwal Saya</a></div>
                    <div class="nav-item"><a class="nav-link" href="laporan.php"><i class="fas fa-file-alt"></i> Laporan Saya</a></div>
                    <div class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profil</a></div>
                <?php endif; ?>
                <div class="nav-item logout-link"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>
                    <i class="fas fa-archive"></i>
                    Arsip Magang
                </h1>
                <p class="subtitle">Kelola arsip peserta magang yang telah selesai</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- Tab Navigation -->
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="showTab('arsip')">
                    <i class="fas fa-archive"></i> Arsip Peserta
                </button>
                <?php if ($role == 'admin'): ?>
                <button class="tab-btn" onclick="showTab('institusi')">
                    <i class="fas fa-building"></i> Kelola Institusi
                </button>
                <?php endif; ?>
            </div>

            <!-- Tab Content: Arsip -->
            <div id="arsip-tab" class="tab-content active">
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-archive"></i>
                        </div>
                        <div class="stat-title">Total Arsip</div>
                        <div class="stat-value"><?= $total_arsip ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-month"></i>
                        </div>
                        <div class="stat-title">Arsip Bulan Ini</div>
                        <div class="stat-value"><?= $arsip_bulan_ini ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-title">Peserta Aktif</div>
                        <div class="stat-value"><?= $peserta_aktif_count ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-title">Total Institusi</div>
                        <div class="stat-value"><?= $total_institusi ?></div>
                    </div>
                </div>

                <!-- Form Arsipkan Peserta -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-plus"></i>
                            Arsipkan Peserta
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="get">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Pilih Peserta *</label>
                                        <select name="arsipkan" class="form-control" required>
                                            <option value="" disabled selected>Pilih Peserta</option>
                                            <?php 
                                            mysqli_data_seek($peserta_aktif, 0);
                                            while($p = mysqli_fetch_assoc($peserta_aktif)): ?>
                                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Keterangan</label>
                                        <select name="keterangan" class="form-control">
                                            <option value="Selesai">Magang Selesai</option>
                                            <option value="Lulus">Lulus Evaluasi</option>
                                            <option value="Berhenti">Berhenti</option>
                                            <option value="Pindah">Pindah Institusi</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-archive"></i>
                                    Arsipkan Peserta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Daftar Arsip -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-list"></i>
                            Daftar Arsip Peserta
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Peserta</th>
                                        <th>Email</th>
                                        <th>Institusi</th>
                                        <th>Keterangan</th>
                                        <th>Tanggal Arsip</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while($row = mysqli_fetch_assoc($arsip)): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['nama']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['institusi'] ?? 'Tidak ada') ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($row['keterangan']) ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($row['tanggal_arsip'])) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="?restore=<?= $row['id'] ?>" 
                                                       class="btn btn-success btn-sm"
                                                       onclick="return confirm('Yakin ingin mengembalikan peserta ini dari arsip?')">
                                                        <i class="fas fa-undo"></i>
                                                        Restore
                                                    </a>
                                                    <a href="?hapus=<?= $row['id'] ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Yakin ingin menghapus data arsip ini secara permanen?')">
                                                        <i class="fas fa-trash"></i>
                                                        Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if (mysqli_num_rows($arsip) == 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <br>
                                                Belum ada data arsip
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Institusi (Admin Only) -->
            <?php if ($role == 'admin'): ?>
            <div id="institusi-tab" class="tab-content">
                <!-- Form Tambah Institusi -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-plus"></i>
                            Tambah Institusi Baru
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Nama Institusi *</label>
                                        <input type="text" name="nama_institusi" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Telepon</label>
                                        <input type="text" name="telepon_institusi" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Alamat</label>
                                        <textarea name="alamat_institusi" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="tambah_institusi" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Tambah Institusi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Daftar Institusi -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-building"></i>
                            Daftar Institusi
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Institusi</th>
                                        <th>Alamat</th>
                                        <th>Telepon</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    mysqli_data_seek($institusi, 0);
                                    while($row = mysqli_fetch_assoc($institusi)): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['nama']) ?></td>
                                            <td><?= htmlspecialchars($row['alamat']) ?></td>
                                            <td><?= htmlspecialchars($row['telepon']) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button onclick="editInstitusi(<?= $row['id'] ?>, '<?= addslashes($row['nama']) ?>', '<?= addslashes($row['alamat']) ?>', '<?= addslashes($row['telepon']) ?>')" 
                                                            class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                        Edit
                                                    </button>
                                                    <a href="?hapus_institusi=<?= $row['id'] ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Yakin ingin menghapus institusi ini?')">
                                                        <i class="fas fa-trash"></i>
                                                        Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if (mysqli_num_rows($institusi) == 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="fas fa-building fa-3x mb-3"></i>
                                                <br>
                                                Belum ada data institusi
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Edit Institusi -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Edit Institusi</h4>
                <span class="close" onclick="closeModal()">×</span>
            </div>
            <div class="modal-body">
                <form method="post" id="editForm">
                    <input type="hidden" name="institusi_id" id="edit_id">
                    <div class="form-group">
                        <label class="form-label">Nama Institusi *</label>
                        <input type="text" name="nama_institusi" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat_institusi" id="edit_alamat" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="telepon_institusi" id="edit_telepon" class="form-control">
                    </div>
                    <div class="form-group">
                        <button type="submit" name="update_institusi" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Institusi
                        </button>
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

        // Modal functions
        function editInstitusi(id, nama, alamat, telepon) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_alamat').value = alamat;
            document.getElementById('edit_telepon').value = telepon;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });

            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8fafc';
                    this.style.transform = 'scale(1.01)';
                    this.style.transition = 'all 0.2s ease';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                    this.style.transform = 'scale(1)';
                });
            });
        });

        // Add confirmation for archive action
        document.addEventListener('DOMContentLoaded', function() {
            const archiveForm = document.querySelector('form[method="get"]');
            if (archiveForm) {
                archiveForm.addEventListener('submit', function(e) {
                    const selectedPeserta = this.querySelector('select[name="arsipkan"]');
                    const selectedKeterangan = this.querySelector('select[name="keterangan"]');
                    
                    if (selectedPeserta.value) {
                        const pesertaName = selectedPeserta.options[selectedPeserta.selectedIndex].text;
                        const keterangan = selectedKeterangan.value;
                        
                        if (!confirm(`Yakin ingin mengarsipkan peserta "${pesertaName}" dengan keterangan "${keterangan}"?`)) {
                            e.preventDefault();
                        }
                    }
                });
            }
        });
    </script>

    <style>
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 500;
            border-radius: 0.375rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .bg-secondary {
            background-color: #6b7280 !important;
            color: white;
        }
        
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6b7280 !important;
        }
        
        .py-4 {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        
        .mb-3 {
            margin-bottom: 1rem;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -0.75rem;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 0.75rem;
        }
        
        @media (max-width: 768px) {
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>