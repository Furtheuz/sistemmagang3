<?php
// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration files with error handling
try {
    include "config/auth.php";
    include "config/db.php";
    checkLogin();
} catch (Exception $e) {
    error_log("Initialization error: " . $e->getMessage());
    die("Error loading configuration. Please check server logs.");
}

$role = $_SESSION['user']['role'] ?? 'user';
$userName = $_SESSION['user']['nama'] ?? 'User';

// Elegant blue theme for all roles
$theme = [
    'primary' => '#1E3A8A',
    'secondary' => '#EFF6FF',
    'accent' => '#60A5FA'
];

$roleIcons = [
    'admin' => '👑',
    'pembimbing' => '👨‍🏫',
    'user' => '👨‍🎓'
];

// Optimized function to fetch counts with caching
function fetchCount($conn, $query, $label) {
    try {
        $result = mysqli_query($conn, $query);
        if ($result === false) {
            throw new Exception("Query failed ($label): " . mysqli_error($conn));
        }
        return mysqli_fetch_assoc($result)['total'];
    } catch (Exception $e) {
        error_log($e->getMessage());
        return 0;
    }
}

// Fetch statistics for admin and pembimbing
if ($role == 'admin' || $role == 'pembimbing') {
    $total_peserta = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta", "Total Peserta");
    $peserta_verified = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'verified'", "Peserta Verified");
    $peserta_pending = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'pending'", "Peserta Pending");
    $peserta_rejected = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'rejected'", "Peserta Rejected");
    $total_institusi = fetchCount($conn, "SELECT COUNT(*) as total FROM institusi", "Total Institusi");
    $total_arsip = fetchCount($conn, "SELECT COUNT(*) as total FROM arsip", "Total Arsip");
    $arsip_bulan_ini = fetchCount($conn, "SELECT COUNT(*) as total FROM arsip WHERE MONTH(tanggal_arsip) = MONTH(NOW()) AND YEAR(tanggal_arsip) = YEAR(NOW())", "Arsip Bulan Ini");
    $peserta_aktif = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta WHERE status='aktif'", "Peserta Aktif");
    $peserta_alumni = fetchCount($conn, "SELECT COUNT(DISTINCT peserta_id) as total FROM arsip", "Peserta Alumni");

    // Fetch participant count per institution
    try {
        $institusi_query = mysqli_query($conn, "
            SELECT i.nama, COUNT(p.id) as jumlah_peserta 
            FROM institusi i 
            LEFT JOIN peserta p ON i.id = p.institusi_id 
            GROUP BY i.id, i.nama
            ORDER BY jumlah_peserta DESC
        ");
        if ($institusi_query === false) {
            throw new Exception("Institution query failed: " . mysqli_error($conn));
        }
        $institusi_data = [];
        while ($row = mysqli_fetch_assoc($institusi_query)) {
            $institusi_data[] = [
                'nama' => $row['nama'],
                'jumlah_peserta' => (int)$row['jumlah_peserta']
            ];
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $institusi_data = [];
    }
}

// User-specific data
if ($role == 'user') {
    $user_id = mysqli_real_escape_string($conn, $_SESSION['user']['id'] ?? '');
    
    // FETCH FOTO PROFIL UNTUK SIDEBAR
    try {
        $foto_query = mysqli_query($conn, "SELECT foto FROM peserta WHERE user_id = '$user_id' LIMIT 1");
        if ($foto_query === false) {
            throw new Exception("Foto query failed: " . mysqli_error($conn));
        }
        $peserta_data = mysqli_fetch_assoc($foto_query);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $peserta_data = null;
    }
    
    // Fetch last report
    try {
        $last_report_query = mysqli_query($conn, "SELECT tanggal FROM laporan WHERE peserta_id = '$user_id' ORDER BY tanggal DESC LIMIT 1");
        if ($last_report_query === false) {
            throw new Exception("Last report query failed: " . mysqli_error($conn));
        }
        $last_report = mysqli_fetch_assoc($last_report_query);
        $last_report_date = $last_report ? date('d-m-Y', strtotime($last_report['tanggal'])) : 'Belum ada laporan';
    } catch (Exception $e) {
        error_log($e->getMessage());
        $last_report_date = 'Error fetching report';
    }
    
    // Fetch schedule for today
    try {
        $schedule_query = mysqli_query($conn, "SELECT tugas, tanggal FROM jadwal WHERE peserta_id = '$user_id' ORDER BY tanggal DESC LIMIT 1");
        if ($schedule_query === false) {
            throw new Exception("Schedule query failed: " . mysqli_error($conn));
        }
        $schedule = mysqli_fetch_assoc($schedule_query);
        $schedule_title = $schedule ? $schedule['tugas'] : 'Tidak ada kegiatan';
        $schedule_time = $schedule ? date('H:i', strtotime($schedule['tanggal'])) : '';
    } catch (Exception $e) {
        error_log($e->getMessage());
        $schedule_title = 'Error fetching schedule';
        $schedule_time = '';
    }
    
    // Fetch report submitted today
    try {
        $report_today_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan WHERE peserta_id = '$user_id' AND DATE(tanggal) = CURDATE()");
        if ($report_today_query === false) {
            throw new Exception("Report today query failed: " . mysqli_error($conn));
        }
        $report_submitted_today = mysqli_fetch_assoc($report_today_query)['total'] > 0;
    } catch (Exception $e) {
        error_log($e->getMessage());
        $report_submitted_today = false;
    }
    
    // Fetch schedule status for today
    try {
        $today_schedule_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM jadwal WHERE peserta_id = '$user_id' AND DATE(tanggal) = CURDATE()");
        if ($today_schedule_query === false) {
            throw new Exception("Today schedule query failed: " . mysqli_error($conn));
        }
        $has_schedule_today = mysqli_fetch_assoc($today_schedule_query)['total'] > 0;
    } catch (Exception $e) {
        error_log($e->getMessage());
        $has_schedule_today = false;
    }
    
    // Fetch PKL start and end date
    try {
        // Cek apakah kolom tanggal_masuk dan tanggal_keluar ada
        $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM peserta LIKE 'tanggal_masuk'");
        $has_tanggal_masuk = mysqli_num_rows($check_columns) > 0;
        $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM peserta LIKE 'tanggal_keluar'");
        $has_tanggal_keluar = mysqli_num_rows($check_columns) > 0;

        if (!$has_tanggal_masuk || !$has_tanggal_keluar) {
            throw new Exception("Required columns (tanggal_masuk or tanggal_keluar) not found in peserta table");
        }

        // Validasi user_id
        if (empty($user_id)) {
            throw new Exception("User ID is empty or invalid");
        }

        // Query PKL dates
        $pkl_query = mysqli_query($conn, "SELECT tanggal_masuk, tanggal_keluar FROM peserta WHERE id = '$user_id'");
        if ($pkl_query === false) {
            throw new Exception("PKL dates query failed: " . mysqli_error($conn));
        }

        $pkl_data = mysqli_fetch_assoc($pkl_query);
        if (!$pkl_data) {
            throw new Exception("No data found for user ID: $user_id");
        }

        // Validasi format tanggal
        $pkl_start_date = !empty($pkl_data['tanggal_masuk']) && strtotime($pkl_data['tanggal_masuk']) !== false 
            ? date('c', strtotime($pkl_data['tanggal_masuk'])) 
            : null;
        $pkl_end_date = !empty($pkl_data['tanggal_keluar']) && strtotime($pkl_data['tanggal_keluar']) !== false 
            ? date('c', strtotime($pkl_data['tanggal_keluar'])) 
            : null;
        $pkl_end_display = !empty($pkl_data['tanggal_keluar']) && strtotime($pkl_data['tanggal_keluar']) !== false 
            ? date('d-m-Y', strtotime($pkl_data['tanggal_keluar'])) 
            : 'Tidak ada data';
    } catch (Exception $e) {
        error_log("PKL Query Error: " . $e->getMessage() . " | User ID: $user_id | Query: SELECT tanggal_masuk, tanggal_keluar FROM peserta WHERE id = '$user_id'");
        $pkl_start_date = null;
        $pkl_end_date = null;
        $pkl_end_display = 'Error fetching PKL dates: ' . $e->getMessage();
    }
    
    // Fetch report and schedule data for the last 7 days
    try {
        $report_data = [];
        $schedule_data = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d M', strtotime($date));
            
            // Reports per day
            $report_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan WHERE peserta_id = '$user_id' AND DATE(tanggal) = '$date'");
            if ($report_query === false) {
                throw new Exception("Report per day query failed: " . mysqli_error($conn));
            }
            $report_data[] = (int)mysqli_fetch_assoc($report_query)['total'];
            
            // Schedules per day
            $schedule_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM jadwal WHERE peserta_id = '$user_id' AND DATE(tanggal) = '$date'");
            if ($schedule_query === false) {
                throw new Exception("Schedule per day query failed: " . mysqli_error($conn));
            }
            $schedule_data[] = (int)mysqli_fetch_assoc($schedule_query)['total'];
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $report_data = array_fill(0, 7, 0);
        $schedule_data = array_fill(0, 7, 0);
        $labels = array_map(function($i) { return date('d M', strtotime("-$i days")); }, range(6, 0));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= ucfirst($role) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= $theme['primary'] ?>;
            --secondary-color: <?= $theme['secondary'] ?>;
            --accent-color: <?= $theme['accent'] ?>;
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
        }

        .sidebar.hidden {
            transform: translateX(-280px);
        }

        .sidebar.visible {
            transform: translateX(0);
        }

        @media (min-width: 769px) {
            .sidebar {
                transform: translateX(0);
            }
            .sidebar.hidden {
                transform: translateX(-280px);
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

        .main-content.no-sidebar {
            margin-left: 0;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            .sidebar {
                transform: translateX(-280px);
            }
            .sidebar.visible {
                transform: translateX(0);
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid var(--accent-color);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .card-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stats-card h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .card-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
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

        .chart-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid var(--accent-color);
            padding: 1.5rem;
            margin-bottom: 2rem;
            max-height: 400px;
            overflow: hidden;
        }

        .chart-title {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-container canvas {
            max-height: 350px;
            width: 100% !important;
        }

        .user-welcome-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid var(--accent-color);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .user-welcome-section h2 {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-welcome-section p {
            font-size: 0.95rem;
            color: var(--text-color);
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .user-welcome-section .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        .user-welcome-section .info-item i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .user-welcome-section .warning {
            color: #ef4444;
            font-weight: 500;
        }

        .countdown-container {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .countdown-item {
            background: var(--accent-color);
            color: white;
            padding: 0.75rem;
            border-radius: 8px;
            text-align: center;
            min-width: 60px;
        }

        .countdown-item span {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .countdown-item small {
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .header-section h1 {
                font-size: 1.5rem;
            }
            .header-section p {
                font-size: 0.9rem;
            }
            .chart-container {
                max-height: 300px;
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
            <?php if ($role === 'user' && isset($peserta_data) && $peserta_data && !empty($peserta_data['foto'])): ?>
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
            $active = $href === 'dashboard.php' ? 'active' : '';
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
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <p>Ikhtisar Magang untuk <?= ucfirst($role) ?> - Pemerintah Provinsi Banten</p>
            </div>
            <?php if ($role == 'admin' || $role == 'pembimbing'): ?>
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stats-card">
            <div class="card-icon"><i class="fas fa-users"></i></div>
            <h3>Total Peserta</h3>
            <p class="card-value"><?= $total_peserta ?? 0 ?></p>
        </div>
        <div class="stats-card">
            <div class="card-icon"><i class="fas fa-check-circle"></i></div>
            <h3>Terverifikasi</h3>
            <p class="card-value"><?= $peserta_verified ?? 0 ?></p>
        </div>
        <div class="stats-card">
            <div class="card-icon"><i class="fas fa-archive"></i></div>
            <h3>Total Arsip</h3>
            <p class="card-value"><?= $total_arsip ?? 0 ?></p>
        </div>
        <div class="stats-card">
            <div class="card-icon"><i class="fas fa-building"></i></div>
            <h3>Total Institusi</h3>
            <p class="card-value"><?= $total_institusi ?? 0 ?></p>
        </div>
    </div>
<?php endif; ?>
            <!-- Statistics Section for Admin and Pembimbing -->
            <?php if ($role == 'admin' || $role == 'pembimbing'): ?>
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Statistik Magang
                    </div>
                    <canvas id="statsChart" style="display: block; max-height: 300px;"></canvas>
                </div>
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="fas fa-university"></i>
                        Jumlah Peserta per Institusi
                    </div>
                    <canvas id="institusiChart" style="display: block; max-height: 300px;"></canvas>
                </div>
            <?php endif; ?>

            <!-- User Welcome Section -->
            <?php if ($role == 'user'): ?>
                <div class="user-welcome-section">
                    <h2><i class="fas fa-user-circle"></i> Selamat Datang</h2>
                    <p>Halo, <?= htmlspecialchars($userName) ?>! Selamat datang di Dashboard Magang Pemerintah Provinsi Banten! Kelola jadwal dan laporan PKL Anda dengan mudah dan pantau progres Anda menuju kesuksesan!</p>
                    <div class="info-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Laporan Terakhir: <?= $last_report_date ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-day"></i>
                        <span>Jadwal Hari Ini: <?= $schedule_title ?><?php if ($schedule_time): ?>, pukul <?= $schedule_time ?>.<?php endif; ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="<?= $report_submitted_today ? '' : 'warning' ?>">
                            Status Laporan Hari Ini: <?= $report_submitted_today ? 'Laporan hari ini telah dikirim.' : 'Anda belum mengirim laporan hari ini. Jangan lupa untuk mengisi ya!' ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Status Jadwal Hari Ini: <?= $has_schedule_today ? 'Ada jadwal hari ini.' : 'Tidak ada jadwal hari ini.' ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span>PKL Berakhir Pada: <?= $pkl_end_display ?></span>
                    </div>
                    <div class="countdown-container" id="countdown">
                        <div class="countdown-item">
                            <span id="days">0</span>
                            <small>Hari</small>
                        </div>
                        <div class="countdown-item">
                            <span id="hours">0</span>
                            <small>Jam</small>
                        </div>
                        <div class="countdown-item">
                            <span id="minutes">0</span>
                            <small>Menit</small>
                        </div>
                        <div class="countdown-item">
                            <span id="seconds">0</span>
                            <small>Detik</small>
                        </div>
                    </div>
                </div>
                <!-- User Statistics Chart -->
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Statistik Laporan dan Jadwal (7 Hari Terakhir)
                    </div>
                    <canvas id="userStatsChart" style="display: block; max-height: 300px;"></canvas>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const userRole = '<?= $role ?>';
        const statsData = {
            total_peserta: <?= $total_peserta ?? 0 ?>,
            peserta_verified: <?= $peserta_verified ?? 0 ?>,
            peserta_pending: <?= $peserta_pending ?? 0 ?>,
            peserta_rejected: <?= $peserta_rejected ?? 0 ?>,
            total_institusi: <?= $total_institusi ?? 0 ?>,
            total_arsip: <?= $total_arsip ?? 0 ?>,
            arsip_bulan_ini: <?= $arsip_bulan_ini ?? 0 ?>,
            peserta_aktif: <?= $peserta_aktif ?? 0 ?>,
            peserta_alumni: <?= $peserta_alumni ?? 0 ?>
        };
        const institusiData = <?= json_encode($institusi_data ?? []) ?>;
        const userStatsData = {
            labels: <?= json_encode($labels ?? []) ?>,
            reports: <?= json_encode($report_data ?? []) ?>,
            schedules: <?= json_encode($schedule_data ?? []) ?>,
            pklStart: '<?= $pkl_start_date ?? '' ?>',
            pklEnd: '<?= $pkl_end_date ?? '' ?>'
        };

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('visible');
            sidebar.classList.toggle('hidden');
            if (sidebar.classList.contains('visible')) {
                mainContent.classList.remove('no-sidebar');
                mainContent.style.marginLeft = '280px';
            } else {
                mainContent.classList.add('no-sidebar');
                mainContent.style.marginLeft = '0';
            }
        }

        // Countdown timer
        function startCountdown() {
            if (!userStatsData.pklEnd) {
                document.getElementById('countdown').innerHTML = '<p>Error: Data PKL tidak tersedia.</p>';
                return;
            }
            const endDate = new Date(userStatsData.pklEnd).getTime();
            const startDate = new Date(userStatsData.pklStart).getTime();
            const now = new Date().getTime();

            if (now >= endDate) {
                document.getElementById('countdown').innerHTML = '<p>PKL telah berakhir.</p>';
                return;
            }

            const distance = endDate - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('days').textContent = days;
            document.getElementById('hours').textContent = hours;
            document.getElementById('minutes').textContent = minutes;
            document.getElementById('seconds').textContent = seconds;
        }

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function () {
            // Start countdown for user
            if (userRole === 'user') {
                startCountdown();
                setInterval(startCountdown, 1000);
            }

            // Admin/Pembimbing Charts
            if (userRole === 'admin' || userRole === 'pembimbing') {
                const statsCanvas = document.getElementById('statsChart');
                if (statsCanvas) {
                    const ctx = statsCanvas.getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Total Peserta', 'Terverifikasi', 'Pending', 'Ditolak', 'Total Institusi', 'Total Arsip', 'Arsip Bulan Ini', 'Peserta Aktif', 'Peserta Alumni'],
                            datasets: [{
                                label: 'Statistik Magang',
                                data: [statsData.total_peserta, statsData.peserta_verified, statsData.peserta_pending, statsData.peserta_rejected, statsData.total_institusi, statsData.total_arsip, statsData.arsip_bulan_ini, statsData.peserta_aktif, statsData.peserta_alumni],
                                borderColor: '#1E3A8A',
                                backgroundColor: 'rgba(30, 58, 138, 0.1)',
                                borderWidth: 2,
                                pointRadius: 4,
                                tension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top' },
                                tooltip: { backgroundColor: 'rgba(0,0,0,0.8)' }
                            },
                            scales: {
                                x: { title: { display: true, text: 'Metrik' }, grid: { display: false } },
                                y: { title: { display: true, text: 'Jumlah' }, beginAtZero: true, grid: { color: '#e5e7eb' } }
                            }
                        }
                    });
                }

                const institusiCanvas = document.getElementById('institusiChart');
                if (institusiCanvas) {
                    const ctx = institusiCanvas.getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: institusiData.map(item => item.nama),
                            datasets: [{
                                label: 'Jumlah Peserta',
                                data: institusiData.map(item => item.jumlah_peserta),
                                backgroundColor: 'rgba(30, 58, 138, 0.6)',
                                borderColor: '#1E3A8A',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top' },
                                tooltip: { backgroundColor: 'rgba(0,0,0,0.8)' }
                            },
                            scales: {
                                x: {
                                    title: { display: true, text: 'Jumlah Peserta' },
                                    beginAtZero: true,
                                    ticks: { stepSize: 1 }
                                },
                                y: {
                                    title: { display: true, text: 'Institusi' },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
            }

            // User Chart (V-shape line chart)
            if (userRole === 'user') {
                const userStatsCanvas = document.getElementById('userStatsChart');
                if (userStatsCanvas) {
                    const ctx = userStatsCanvas.getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: userStatsData.labels,
                            datasets: [
                                {
                                    label: 'Laporan',
                                    data: userStatsData.reports,
                                    borderColor: '#1E3A8A',
                                    backgroundColor: 'rgba(30, 58, 138, 0.1)',
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    tension: 0.3,
                                    fill: false
                                },
                                {
                                    label: 'Jadwal',
                                    data: userStatsData.schedules,
                                    borderColor: '#60A5FA',
                                    backgroundColor: 'rgba(96, 165, 250, 0.1)',
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    tension: 0.3,
                                    fill: false
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top' },
                                tooltip: { backgroundColor: 'rgba(0,0,0,0.8)' }
                            },
                            scales: {
                                x: { title: { display: true, text: 'Tanggal' }, grid: { display: false } },
                                y: { 
                                    title: { display: true, text: 'Jumlah' }, 
                                    beginAtZero: true, 
                                    grid: { color: '#e5e7eb' },
                                    ticks: { stepSize: 1 }
                                }
                            }
                        }
                    });
                }
            }

            // Animate stats cards
            if (userRole === 'admin' || userRole === 'pembimbing') {
                const cards = document.querySelectorAll('.stats-card');
                cards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        card.style.transition = 'all 0.5s ease';
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 50);
                    }, index * 100);
                });
            }
        });
    </script>
</body>
</html>