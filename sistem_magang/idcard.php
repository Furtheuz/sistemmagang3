<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

$role = $_SESSION['user']['role'];
$userName = $_SESSION['user']['nama'] ?? 'User';
$pesan = '';

// Role-based icons
$roleIcons = [
    'admin' => '👑',
    'pembimbing' => '👨‍🏫',
    'user' => '👨‍🎓'
];

// Pagination settings
$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$whereClause = $search ? "WHERE p.nama LIKE '%$search%'" : '';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_peserta']) && $role == 'admin') {
    $peserta_id = (int)($_POST['peserta_id'] ?? 0);
    if ($peserta_id > 0) {
        $stmt = $conn->prepare("DELETE FROM peserta WHERE id = ?");
        $stmt->bind_param("i", $peserta_id);
        $pesan = $stmt->execute() && $stmt->affected_rows > 0
            ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Peserta berhasil dihapus!</div>'
            : '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal menghapus peserta!</div>';
        $stmt->close();
    }
}

// Query untuk mendapatkan data peserta dengan pagination
$pesertaQuery = "SELECT p.* FROM peserta p JOIN users u ON p.user_id=u.id $whereClause ORDER BY p.nama ASC LIMIT $limit OFFSET $offset";
$peserta = mysqli_query($conn, $pesertaQuery);

// Query untuk total peserta (untuk pagination)
$totalPesertaQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta p JOIN users u ON p.user_id=u.id $whereClause");
$totalPesertaData = mysqli_fetch_assoc($totalPesertaQuery);
$totalPeserta = $totalPesertaData['total'];
$totalPages = ceil($totalPeserta / $limit);

// Query untuk statistik with error checking
$pesertaAktifQuery = mysqli_query($conn, "SELECT * FROM peserta WHERE status='aktif'");
$pesertaAktif = $pesertaAktifQuery ? mysqli_num_rows($pesertaAktifQuery) : 0;

$pesertaSelesaiQuery = mysqli_query($conn, "SELECT * FROM peserta WHERE status='selesai'");
$pesertaSelesai = $pesertaSelesaiQuery ? mysqli_num_rows($pesertaSelesaiQuery) : 0;

$riwayatCetakQuery = mysqli_query($conn, "SELECT * FROM riwayat_cetak_idcard");
$riwayatCetak = $riwayatCetakQuery ? mysqli_num_rows($riwayatCetakQuery) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card Peserta - <?= ucfirst($role) ?></title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
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

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
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
            color: var(--muted-color);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-box {
            display: flex;
            gap: 1rem;
            align-items: center;
            background: #f8fafc;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 1rem;
        }

        .search-input {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            background: white;
            color: var(--text-color);
            width: 300px;
        }

        .search-input::placeholder {
            color: var(--muted-color);
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(96,165,250,0.2);
        }

        .table {
            width: 100%;
            margin: 0;
        }

        .table thead th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
            padding: 1rem;
            border: none;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            text-align: center;
        }

        .table tbody td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            vertical-align: middle;
            text-align: center;
        }

        .table tbody tr:hover {
            background: var(--secondary-color);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-aktif {
            background: #dcfce7;
            color: #166534;
        }

        .status-selesai {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-nonaktif {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-group {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
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

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
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

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }

/* Styling untuk Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
}

.pagination a, .pagination span {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.pagination a {
    background: #1E3A8A; /* Warna biru sesuai screenshot */
    color: white;
    border: 1px solid #1E3A8A;
}

.pagination a:hover {
    background: #60A5FA; /* Warna aksen biru lebih terang */
    border-color: #60A5FA;
}

.pagination span {
    background: #e5e7eb; /* Latar belakang abu-abu terang */
    color: #6b7280; /* Teks abu-abu */
    border: 1px solid #d1d5db;
}

/* Disabled state untuk tombol */
.pagination .disabled a {
    background: #f8fafc; /* Latar belakang abu-abu untuk disabled */
    color: #6b7280;
    border-color: #d1d5db;
    cursor: not-allowed;
}

.pagination .disabled a:hover {
    background: #f8fafc;
    color: #6b7280;
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

        .modal-content {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 1rem;
        }

        .avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .header-section h1 {
                font-size: 1.5rem;
            }
            .header-section p {
                font-size: 0.9rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .table-responsive {
                overflow-x: auto;
            }
            .search-box {
                width: 100%;
            }
            .search-input {
                width: 100%;
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
                    <?= $roleIcons[$role] ?>
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
                    $active = strpos($href, 'idcard.php') !== false ? 'active' : '';
                    echo "<div class='nav-item'><a class='nav-link $active' href='$href'><i class='$icon'></i> $title</a></div>";
                }
                ?>
                <div class="nav-item logout-link"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header-section">
                <h1><i class="fas fa-id-card"></i> ID Card Peserta</h1>
                <p>Kelola dan cetak ID Card untuk semua peserta dengan mudah</p>
            </div>

            <?= $pesan ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-title">Total Peserta</div>
                    <div class="stat-value"><?= $totalPeserta ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                    <div class="stat-title">Peserta Aktif</div>
                    <div class="stat-value"><?= $pesertaAktif ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-title">Peserta Selesai</div>
                    <div class="stat-value"><?= $pesertaSelesai ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-print"></i></div>
                    <div class="stat-title">Riwayat Cetak</div>
                    <div class="stat-value"><?= $riwayatCetak ?></div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Peserta</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" onclick="cetakTerpilih()" id="btnCetakTerpilih" disabled>
                                <i class="fas fa-print"></i> Cetak Terpilih (<span id="jumlahTerpilih">0</span>)
                            </button>
                            <a href="riwayat_cetak.php" class="btn btn-primary">
                                <i class="fas fa-history"></i> Riwayat Cetak
                            </a>
                        </div>
                        <div class="search-box">
                            <input type="text" class="search-input form-control" placeholder="Cari peserta..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary btn-sm" onclick="searchPeserta()"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll" onchange="toggleAllCheckbox()"></th>
                                    <th>No.</th>
                                    <th>Nama Peserta</th>
                                    <th>Status</th>
                                    <th>Terakhir Dicetak</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pesertaTable">
                                <?php 
                                if ($peserta && mysqli_num_rows($peserta) > 0) {
                                    $no = $offset + 1;
                                    while ($p = mysqli_fetch_assoc($peserta)):
                                        $lastPrintQuery = mysqli_query($conn, "SELECT created_at FROM riwayat_cetak_idcard WHERE peserta_id = '" . $p['id'] . "' ORDER BY created_at DESC LIMIT 1");
                                        $lastPrintData = $lastPrintQuery ? mysqli_fetch_assoc($lastPrintQuery) : null;
                                        // Assuming 'photo' field exists in peserta table
                                        $photoPath = !empty($p['photo']) && file_exists($p['photo']) ? $p['photo'] : null;
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_peserta[]" value="<?= $p['id'] ?>" class="peserta-checkbox"></td>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($photoPath): ?>
                                                <img src="<?= htmlspecialchars($photoPath) ?>" alt="Profile" class="avatar-sm me-3">
                                            <?php else: ?>
                                                <div class="avatar-sm me-3"><?= strtoupper(substr($p['nama'], 0, 1)) ?></div>
                                            <?php endif; ?>
                                            <div class="fw-semibold"><?= htmlspecialchars($p['nama']) ?></div>
                                        </div>
                                    </td>
                                    <td><span class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                                    <td>
                                        <?php if ($lastPrintData && isset($lastPrintData['created_at'])): ?>
                                            <small><?= date('d/m/Y H:i', strtotime($lastPrintData['created_at'])) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">Belum pernah</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-group">
                                        <?php if ($role == 'admin'): ?>
                                            <button class="btn btn-danger btn-sm" onclick="hapusPeserta(<?= $p['id'] ?>, '<?= addslashes($p['nama']) ?>')"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php } else { ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><h5>Tidak ada data peserta</h5></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-wrapper">
    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" tabindex="-1"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
                </li>
                <li class="page-item disabled">
                    <span class="page-link">Halaman <?= $page ?> dari <?= $totalPages ?></span>
                </li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Selanjutnya <i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
    <small class="text-muted">Menampilkan <?= mysqli_num_rows($peserta) ?> dari <?= $totalPeserta ?> peserta</small>
</div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Hapus Peserta -->
    <?php if ($role == 'admin'): ?>
    <div class="modal fade" id="deletePesertaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="peserta_id" id="delete_peserta_id">
                        <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> Hapus peserta ini?</div>
                        <p class="text-center"><strong>Nama:</strong> <span id="delete_peserta_nama"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="hapus_peserta" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

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

        // Search functionality
        function searchPeserta() {
            const input = document.getElementById('searchInput');
            const searchValue = input.value;
            window.location.href = '?page=1&search=' + encodeURIComponent(searchValue);
        }

        // Real-time search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchPeserta();
            }
        });

        // Checkbox functionality
        function toggleAllCheckbox() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.peserta-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
            updateCetakButton();
        }

        function updateCetakButton() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            const btnCetak = document.getElementById('btnCetakTerpilih');
            const jumlahSpan = document.getElementById('jumlahTerpilih');
            jumlahSpan.textContent = checkboxes.length;
            btnCetak.disabled = checkboxes.length === 0;
            btnCetak.classList.toggle('btn-primary', checkboxes.length > 0);
            btnCetak.classList.toggle('btn-secondary', checkboxes.length === 0);
        }

        // Add event listeners to all checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox');
            checkboxes.forEach(checkbox => checkbox.addEventListener('change', updateCetakButton));
            updateCetakButton();

            // Auto-hide alerts
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
        });

        // Cetak terpilih function
        function cetakTerpilih() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Pilih minimal satu peserta untuk dicetak!');
                return;
            }
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);
            window.open('cetak_massal.php?ids=' + selectedIds.join(','), '_blank');
            fetch('log_cetak.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ peserta_ids: selectedIds, jenis_cetak: 'massal' })
            }).then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      alert('Riwayat cetak berhasil disimpan!');
                      location.reload();
                  } else {
                      alert('Gagal menyimpan riwayat cetak: ' + data.message);
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  alert('Terjadi kesalahan saat menyimpan riwayat cetak');
              });
        }

        // Delete peserta function
        function hapusPeserta(id, nama) {
            document.getElementById('delete_peserta_id').value = id;
            document.getElementById('delete_peserta_nama').textContent = nama;
            new bootstrap.Modal(document.getElementById('deletePesertaModal')).show();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                document.getElementById('selectAll').checked = true;
                toggleAllCheckbox();
            }
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                cetakTerpilih();
            }
            if (e.key === 'Escape') {
                document.getElementById('selectAll').checked = false;
                toggleAllCheckbox();
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(function() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            if (checkboxes.length === 0) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>