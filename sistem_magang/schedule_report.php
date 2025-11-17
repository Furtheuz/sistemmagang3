<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['id'];
$userName = $user['nama'] ?? 'User';
$pesan = '';

// Ambil data peserta untuk user (foto profil)
$peserta_data = null;
if ($role === 'user') {
    $stmt = $conn->prepare("SELECT p.*, i.nama AS institusi_nama FROM peserta p LEFT JOIN institusi i ON p.institusi_id = i.id WHERE p.user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $peserta_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Role-based styling
$roleIcons = ['admin' => '👑', 'pembimbing' => '👨‍🏫', 'user' => ''];

// Tentukan tab aktif
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], ['jadwal', 'laporan']) ? $_GET['tab'] : 'jadwal';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_jadwal']) && $role == 'pembimbing') {
        $peserta_id = (int)($_POST['peserta_id'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? '';
        $tugas = trim($_POST['tugas'] ?? '');
        $minggu = (int)($_POST['minggu'] ?? 0);

        if ($peserta_id && $tanggal && $tugas && $minggu) {
            $check_stmt = $conn->prepare("SELECT id FROM pasangan WHERE peserta_id = ? AND pembimbing_id = ? AND status = 'accepted'");
            $check_stmt->bind_param("ii", $peserta_id, $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO jadwal (peserta_id, tanggal, tugas, pembimbing_id, minggu) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isssi", $peserta_id, $tanggal, $tugas, $user_id, $minggu);
                $pesan = $stmt->execute() 
                    ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal berhasil ditambahkan!</div>'
                    : '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal menambahkan jadwal!</div>';
                $stmt->close();
            } else {
                $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Izin ditolak untuk peserta ini!</div>';
            }
            $check_stmt->close();
        } else {
            $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Data jadwal tidak lengkap!</div>';
        }
    }

    if (isset($_POST['hapus_jadwal']) && $role == 'pembimbing') {
        $jadwal_id = (int)($_POST['jadwal_id'] ?? 0);
        if ($jadwal_id > 0) {
            $check_stmt = $conn->prepare("SELECT id FROM jadwal WHERE id = ? AND pembimbing_id = ?");
            $check_stmt->bind_param("ii", $jadwal_id, $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $stmt = $conn->prepare("DELETE FROM jadwal WHERE id = ?");
                $stmt->bind_param("i", $jadwal_id);
                $pesan = $stmt->execute() && $stmt->affected_rows > 0
                    ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal dihapus!</div>'
                    : '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal menghapus jadwal!</div>';
                $stmt->close();
            } else {
                $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Izin ditolak untuk menghapus!</div>';
            }
            $check_stmt->close();
        }
        header("Location: schedule_report.php?tab=jadwal");
        exit();
    }

    if (isset($_POST['hapus_pasangan']) && $role == 'admin') {
        $pasangan_id = (int)($_POST['pasangan_id'] ?? 0);
        if ($pasangan_id > 0) {
            $stmt = $conn->prepare("DELETE FROM pasangan WHERE id = ?");
            $stmt->bind_param("i", $pasangan_id);
            $pesan = $stmt->execute() && $stmt->affected_rows > 0
                ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Pasangan dihapus!</div>'
                : '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal menghapus pasangan!</div>';
            $stmt->close();
        }
        header("Location: schedule_report.php?tab=jadwal");
        exit();
    }

    if (isset($_POST['tambah_pasangan']) && $role == 'admin') {
        $peserta_id = (int)($_POST['peserta_id'] ?? 0);
        $pembimbing_id = (int)($_POST['pembimbing_id'] ?? 0);
        if ($peserta_id && $pembimbing_id) {
            $stmt = $conn->prepare("INSERT INTO pasangan (peserta_id, pembimbing_id, status) VALUES (?, ?, 'accepted')");
            $stmt->bind_param("ii", $peserta_id, $pembimbing_id);
            $pesan = $stmt->execute()
                ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Pasangan berhasil ditambahkan!</div>'
                : '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal menambahkan pasangan!</div>';
            $stmt->close();
        } else {
            $pesan = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Data pasangan tidak lengkap!</div>';
        }
    }

    if ($role == 'user' && isset($_POST['isi'])) {
        $tanggal = $_POST['tanggal'] ?? '';
        $kegiatan = $_POST['kegiatan'] ?? '';
        $q = mysqli_query($conn, "SELECT id FROM peserta WHERE user_id = $user_id LIMIT 1");
        $peserta = mysqli_fetch_assoc($q);
        if ($peserta && $tanggal && $kegiatan) {
            $peserta_id = $peserta['id'];
            mysqli_query($conn, "INSERT INTO laporan (peserta_id, tanggal, kegiatan) VALUES ('$peserta_id', '$tanggal', '$kegiatan')");
            $pesan = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Laporan disimpan.</div>";
        } else {
            $pesan = "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Gagal menyimpan laporan.</div>";
        }
    }
}

if (isset($_GET['validasi']) && $role == 'pembimbing') {
    $id = (int)$_GET['validasi'];
    mysqli_query($conn, "UPDATE laporan SET validasi='valid' WHERE id=$id");
    header("Location: schedule_report.php?tab=laporan");
    exit();
}

if (isset($_GET['hapus']) && ($role == 'user' || $role == 'admin')) {
    $id = (int)$_GET['hapus'];
    $condition = $role == 'user' 
        ? "WHERE id=$id AND peserta_id IN (SELECT id FROM peserta WHERE user_id=$user_id) AND validasi='belum'"
        : "WHERE id=$id";
    mysqli_query($conn, "DELETE FROM laporan $condition");
    header("Location: schedule_report.php?tab=laporan");
    exit();
}

// Query data berdasarkan tab
if ($activeTab == 'jadwal') {
    if ($role == 'admin') {
        $peserta = mysqli_query($conn, "SELECT * FROM peserta ORDER BY nama");
        $pembimbing = mysqli_query($conn, "SELECT * FROM users WHERE role = 'pembimbing' ORDER BY nama");
        $pasangan = mysqli_query($conn, "SELECT pa.id, p.nama AS peserta_nama, p.bidang, u.nama AS pembimbing_nama FROM pasangan pa JOIN peserta p ON pa.peserta_id = p.id JOIN users u ON pa.pembimbing_id = u.id WHERE pa.status = 'accepted' ORDER BY p.nama");
    
    } elseif ($role == 'pembimbing') {
        $peserta = mysqli_query($conn, "SELECT p.* FROM pasangan pa JOIN peserta p ON pa.peserta_id = p.id WHERE pa.pembimbing_id = '$user_id' AND pa.status = 'accepted' ORDER BY p.nama");
        $jadwal = mysqli_query($conn, "SELECT j.*, p.nama AS peserta FROM jadwal j JOIN peserta p ON j.peserta_id = p.id WHERE j.pembimbing_id = '$user_id' ORDER BY j.minggu, j.tanggal");
    } else {
        $peserta_id = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM peserta WHERE user_id = '$user_id'"))['id'] ?? 0;
        $jadwal = $peserta_id 
            ? mysqli_query($conn, "SELECT j.*, u.nama AS pembimbing FROM jadwal j JOIN users u ON j.pembimbing_id = u.id WHERE j.peserta_id = '$peserta_id' ORDER BY j.minggu, j.tanggal")
            : false;
    }
} elseif ($activeTab == 'laporan') {
    if ($role == 'user') {
        $laporan_query = "SELECT l.*, p.nama FROM laporan l JOIN peserta p ON l.peserta_id = p.id WHERE p.user_id = $user_id ORDER BY l.tanggal DESC";
    } elseif ($role == 'pembimbing') {
        $laporan_query = "SELECT l.*, u.nama 
                         FROM laporan l 
                         JOIN peserta p ON l.peserta_id = p.id 
                         JOIN users u ON p.user_id = u.id 
                         JOIN pasangan pa ON p.id = pa.peserta_id 
                         WHERE pa.pembimbing_id = $user_id AND pa.status = 'accepted' 
                         ORDER BY l.tanggal DESC";
    } else { // admin
        $laporan_query = "SELECT l.*, u.nama 
                         FROM laporan l 
                         JOIN peserta p ON l.peserta_id = p.id 
                         JOIN users u ON p.user_id = u.id 
                         ORDER BY l.tanggal DESC";
    }
    $laporan = mysqli_query($conn, $laporan_query);
    $total_laporan = mysqli_num_rows($laporan);
    $belum_validasi = $sudah_validasi = 0;
    while ($l = mysqli_fetch_assoc($laporan)) {
        if ($l['validasi'] == 'belum') $belum_validasi++;
        else $sudah_validasi++;
    }
    mysqli_data_seek($laporan, 0);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal & Laporan - <?= ucfirst($role) ?></title>
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
    overflow-x: auto;
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
        padding: 1rem;
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
    overflow-x: auto;
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

.tab-buttons {
    margin-bottom: 2rem;
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: 1px solid #d1d5db;
    background: white;
    color: #374151;
    cursor: pointer;
}

.tab-btn.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.tab-btn:hover:not(.active) {
    background: var(--secondary-color);
    border-color: var(--primary-color);
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
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
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

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin-top: 1rem;
}

.table th,
.table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    word-wrap: break-word;
    overflow-wrap: break-word;
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

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-verified {
    background: #d1fae5;
    color: #065f46;
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

.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -0.75rem;
}

.col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
    padding: 0 0.75rem;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding: 0 0.75rem;
}

.col-md-12 {
    flex: 0 0 100%;
    max-width: 100%;
    padding: 0 0.75rem;
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
    .stats-card {
        margin-bottom: 1rem;
    }
    .col-md-4, .col-md-6, .col-md-12 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .tab-buttons {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .main-content {
        margin-left: 0;
    }
    .table {
        min-width: 0;
    }
}

* {
    box-sizing: border-box;
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
                    $active = strpos($href, $activeTab) !== false ? 'active' : '';
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
                <h1><i class="fas fa-calendar-alt"></i> Jadwal & Laporan</h1>
                <p>Kelola jadwal dan laporan magang</p>
            </div>

            <?= $pesan ?>

            <!-- Tab Buttons -->
            <div class="tab-buttons">
                <button class="tab-btn <?= $activeTab == 'jadwal' ? 'active' : '' ?>" onclick="location.href='?tab=jadwal'"><i class="fas fa-calendar"></i> Jadwal</button>
                <button class="tab-btn <?= $activeTab == 'laporan' ? 'active' : '' ?>" onclick="location.href='?tab=laporan'"><i class="fas fa-file-alt"></i> Laporan</button>
            </div>

            <!-- Jadwal Content -->
            <?php if ($activeTab == 'jadwal'): ?>
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-plus-circle"></i> Tambah Pasangan</h3>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Peserta *</label>
                                            <select name="peserta_id" class="form-control" required>
                                                <option value="" disabled selected>Pilih Peserta</option>
                                                <?php while ($p = mysqli_fetch_assoc($peserta)): ?>
                                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Pembimbing *</label>
                                            <select name="pembimbing_id" class="form-control" required>
                                                <option value="" disabled selected>Pilih Pembimbing</option>
                                                <?php while ($u = mysqli_fetch_assoc($pembimbing)): ?>
                                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="tambah_pasangan" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pasangan</button>
                            </form>
                        </div>
                    </div>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-users"></i> Daftar Pasangan</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Peserta</th>
                                            <th>Bidang</th>
                                            <th>Pembimbing</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; while ($p = mysqli_fetch_assoc($pasangan)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($p['peserta_nama']) ?></td>
                                                <td><?= htmlspecialchars($p['bidang']) ?></td>
                                                <td><?= htmlspecialchars($p['pembimbing_nama']) ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-danger" onclick="hapusPasangan(<?= $p['id'] ?>, '<?= addslashes($p['peserta_nama']) ?>', '<?= addslashes($p['pembimbing_nama']) ?>')">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($pasangan) == 0): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="py-4">
                                                        <i class="fas fa-users" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                                                        <p>Tidak Ada Pasangan</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($role == 'pembimbing'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-plus-circle"></i> Buat Jadwal</h3>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Peserta *</label>
                                            <select name="peserta_id" class="form-control" required>
                                                <option value="" disabled selected>Pilih Peserta</option>
                                                <?php while ($p = mysqli_fetch_assoc($peserta)): ?>
                                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Tanggal *</label>
                                            <input type="date" name="tanggal" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="form-label">Minggu *</label>
                                            <select name="minggu" class="form-control" required>
                                                <option value="" disabled selected>Pilih</option>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?= $i ?>">Minggu <?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Tugas/Kegiatan *</label>
                                            <input type="text" name="tugas" class="form-control" placeholder="Masukkan tugas" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="tambah_jadwal" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Jadwal</button>
                            </form>
                        </div>
                    </div>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-check"></i> Daftar Jadwal</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Peserta</th>
                                            <th>Minggu</th>
                                            <th>Tugas</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; while ($j = mysqli_fetch_assoc($jadwal)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= date('d/m/Y', strtotime($j['tanggal'])) ?></td>
                                                <td><?= htmlspecialchars($j['peserta']) ?></td>
                                                <td>Minggu <?= $j['minggu'] ?></td>
                                                <td><?= htmlspecialchars($j['tugas']) ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-danger" onclick="hapusJadwal(<?= $j['id'] ?>, '<?= addslashes($j['peserta']) ?>', '<?= $j['tanggal'] ?>')">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($jadwal) == 0): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <div class="py-4">
                                                        <i class="fas fa-calendar-times" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                                                        <p>Tidak Ada Jadwal</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($role == 'user'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-check"></i> Daftar Jadwal</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Pembimbing</th>
                                            <th>Minggu</th>
                                            <th>Tugas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; while ($j = mysqli_fetch_assoc($jadwal)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= date('d/m/Y', strtotime($j['tanggal'])) ?></td>
                                                <td><?= htmlspecialchars($j['pembimbing']) ?></td>
                                                <td>Minggu <?= $j['minggu'] ?></td>
                                                <td><?= htmlspecialchars($j['tugas']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($jadwal) == 0): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="py-4">
                                                        <i class="fas fa-calendar-times" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                                                        <p>Tidak Ada Jadwal</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <!-- Laporan Content -->
            <?php elseif ($activeTab == 'laporan'): ?>
                <div class="stats-grid">
                    <div class="stats-card">
                        <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                        <h3>Total Laporan</h3>
                        <p class="card-value"><?= number_format($total_laporan) ?></p>
                    </div>
                    <div class="stats-card">
                        <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                        <h3>Sudah Validasi</h3>
                        <p class="card-value"><?= number_format($sudah_validasi) ?></p>
                    </div>
                    <div class="stats-card">
                        <div class="card-icon"><i class="fas fa-hourglass-half"></i></div>
                        <h3>Belum Validasi</h3>
                        <p class="card-value"><?= number_format($belum_validasi) ?></p>
                    </div>
                </div>

                <?php if ($role == 'user'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-plus-circle"></i> Tambah Laporan</h3>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Tanggal *</label>
                                            <input type="date" name="tanggal" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label class="form-label">Kegiatan *</label>
                                            <textarea name="kegiatan" class="form-control" rows="3" placeholder="Deskripsi kegiatan" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="isi" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Laporan</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Daftar Laporan</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <?php if ($role != 'user'): ?><th>Peserta</th><?php endif; ?>
                                        <th>Tanggal</th>
                                        <th>Kegiatan</th>
                                        <th>Status</th>
                                        <?php if ($role == 'pembimbing' || $role == 'admin' || $role == 'user'): ?><th>Aksi</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; while ($l = mysqli_fetch_assoc($laporan)): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <?php if ($role != 'user'): ?>
                                                <td><?= htmlspecialchars($l['nama']) ?></td>
                                            <?php endif; ?>
                                            <td><?= date('d/m/Y', strtotime($l['tanggal'])) ?></td>
                                            <td><?= htmlspecialchars($l['kegiatan']) ?></td>
                                            <td>
                                                <span class="status-badge <?= $l['validasi'] == 'belum' ? 'status-pending' : 'status-verified' ?>">
                                                    <?= $l['validasi'] == 'belum' ? 'Belum Validasi' : 'Valid' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($role == 'pembimbing' && $l['validasi'] == 'belum'): ?>
                                                        <a href="?validasi=<?= $l['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Validasi laporan?')">
                                                            <i class="fas fa-check"></i> Validasi
                                                        </a>
                                                    <?php elseif ($role == 'user' && $l['validasi'] == 'belum'): ?>
                                                        <a href="?hapus=<?= $l['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus laporan?')">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </a>
                                                    <?php elseif ($role == 'admin'): ?>
                                                        <a href="?hapus=<?= $l['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus laporan?')">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($total_laporan == 0): ?>
                                        <tr>
                                            <td colspan="<?= $role == 'user' ? 5 : 6 ?>" class="text-center">
                                                <div class="py-4">
                                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                                                    <p>Tidak Ada Laporan</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Modal Hapus Jadwal -->
            <?php if ($role == 'pembimbing'): ?>
                <div id="deleteJadwalModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4>Konfirmasi Hapus Jadwal</h4>
                            <span class="close" onclick="closeModal('deleteJadwalModal')">×</span>
                        </div>
                        <div class="modal-body">
                            <form method="post">
                                <input type="hidden" name="jadwal_id" id="delete_jadwal_id">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Apakah Anda yakin ingin menghapus jadwal ini?
                                </div>
                                <p class="text-center"><strong>Peserta:</strong> <span id="delete_peserta"></span></p>
                                <p class="text-center"><strong>Tanggal:</strong> <span id="delete_tanggal"></span></p>
                                <div style="text-align: right; margin-top: 1rem;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('deleteJadwalModal')">Batal</button>
                                    <button type="submit" name="hapus_jadwal" class="btn btn-sm btn-danger">Hapus</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Modal Hapus Pasangan -->
            <?php if ($role == 'admin'): ?>
                <div id="deletePasanganModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4>Konfirmasi Hapus Pasangan</h4>
                            <span class="close" onclick="closeModal('deletePasanganModal')">×</span>
                        </div>
                        <div class="modal-body">
                            <form method="post">
                                <input type="hidden" name="pasangan_id" id="delete_pasangan_id">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Apakah Anda yakin ingin menghapus pasangan ini?
                                </div>
                                <p class="text-center"><strong>Peserta:</strong> <span id="delete_pasangan_peserta"></span></p>
                                <p class="text-center"><strong>Pembimbing:</strong> <span id="delete_pasangan_pembimbing"></span></p>
                                <div style="text-align: right; margin-top: 1rem;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('deletePasanganModal')">Batal</button>
                                    <button type="submit" name="hapus_pasangan" class="btn btn-sm btn-danger">Hapus</button>
                                </div>
                            </form>
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

        // Modal functions
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function hapusJadwal(id, peserta, tanggal) {
            document.getElementById('delete_jadwal_id').value = id;
            document.getElementById('delete_peserta').textContent = peserta;
            document.getElementById('delete_tanggal').textContent = new Date(tanggal).toLocaleDateString('id-ID');
            document.getElementById('deleteJadwalModal').style.display = 'block';
        }

        function hapusPasangan(id, peserta, pembimbing) {
            document.getElementById('delete_pasangan_id').value = id;
            document.getElementById('delete_pasangan_peserta').textContent = peserta;
            document.getElementById('delete_pasangan_pembimbing').textContent = pembimbing;
            document.getElementById('deletePasanganModal').style.display = 'block';
        }

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>