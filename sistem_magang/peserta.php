<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

// Initialize variables
$message = '';
$messageType = '';

$role = $_SESSION['user']['role'];
$userName = $role === 'user' ? ($_SESSION['user']['name'] ?? 'User') : ucfirst($role);

// Role-based icons
$roleIcons = [
    'admin' => '👑',
    'pembimbing' => '👨‍🏫',
    'user' => '👨‍🎓'
];

// Available bidang options
$bidang_options = [
    'Sekretariat',
    'Bina Marga',
    'Tata Ruang',
    'Umum',
    'Jasa Konstruksi'
];

// Pagination dan Parameter Pencarian
$limit = 10; // Jumlah record per halaman
$page_peserta = isset($_GET['page_peserta']) ? max(1, (int)$_GET['page_peserta']) : 1;
$page_institusi = isset($_GET['page_institusi']) ? max(1, (int)$_GET['page_institusi']) : 1;
$offset_peserta = ($page_peserta - 1) * $limit;
$offset_institusi = ($page_institusi - 1) * $limit;

$search_peserta = isset($_GET['search_peserta']) ? mysqli_real_escape_string($conn, $_GET['search_peserta']) : '';
$search_institusi = isset($_GET['search_institusi']) ? mysqli_real_escape_string($conn, $_GET['search_institusi']) : '';

// Peserta Query (Peserta Aktif)
$peserta_query = "SELECT p.*, i.nama as institusi 
                  FROM peserta p 
                  LEFT JOIN institusi i ON p.institusi_id = i.id 
                  WHERE p.status = 'aktif' 
                  AND (p.nama LIKE '%$search_peserta%' OR p.email LIKE '%$search_peserta%' OR p.bidang LIKE '%$search_peserta%')
                  ORDER BY p.tanggal_masuk DESC 
                  LIMIT $limit OFFSET $offset_peserta";
$peserta = mysqli_query($conn, $peserta_query);

// Total Peserta untuk Pagination
$total_peserta_query = "SELECT COUNT(*) as total 
                        FROM peserta p 
                        WHERE p.status = 'aktif' 
                        AND (p.nama LIKE '%$search_peserta%' OR p.email LIKE '%$search_peserta%' OR p.bidang LIKE '%$search_peserta%')";
$total_peserta_result = mysqli_fetch_assoc(mysqli_query($conn, $total_peserta_query));
$total_peserta_pages = ceil($total_peserta_result['total'] / $limit);

// Pending Registrations Query
$pending_registrations_query = "SELECT * 
                                FROM register 
                                WHERE status = 'pending' 
                                AND (nama LIKE '%$search_peserta%' OR email LIKE '%$search_peserta%')
                                ORDER BY created_at DESC 
                                LIMIT $limit OFFSET $offset_peserta";
$pending_registrations = mysqli_query($conn, $pending_registrations_query);

// Total Pending Registrations untuk Pagination
$total_pending_query = "SELECT COUNT(*) as total 
                        FROM register 
                        WHERE status = 'pending' 
                        AND (nama LIKE '%$search_peserta%' OR email LIKE '%$search_peserta%')";
$total_pending_result = mysqli_fetch_assoc(mysqli_query($conn, $total_pending_query));
$total_pending_pages = ceil($total_pending_result['total'] / $limit);

// Institusi Query
$institusi_query = "SELECT * 
                    FROM institusi 
                    WHERE nama LIKE '%$search_institusi%' 
                    ORDER BY nama 
                    LIMIT $limit OFFSET $offset_institusi";
$institusi = mysqli_query($conn, $institusi_query);

// Total Institusi untuk Pagination
$total_institusi_query = "SELECT COUNT(*) as total 
                          FROM institusi 
                          WHERE nama LIKE '%$search_institusi%'";
$total_institusi_result = mysqli_fetch_assoc(mysqli_query($conn, $total_institusi_query));
$total_institusi_pages = ceil($total_institusi_result['total'] / $limit);

// Function to upload photo
function uploadFoto($file, $peserta_id) {
    $target_dir = "Uploads/peserta/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = "peserta_" . $peserta_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ['success' => false, 'message' => 'File bukan gambar yang valid'];
    }
    
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    }
    
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif") {
        return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & GIF yang diizinkan'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file'];
    }
}

// Automatic archiving for completed internships
$today = date('Y-m-d');
$completed_peserta = mysqli_query($conn, "SELECT id FROM peserta WHERE tanggal_keluar < '$today' AND status = 'aktif'");
while ($row = mysqli_fetch_assoc($completed_peserta)) {
    $peserta_id = $row['id'];
    $keterangan = 'Selesai';
    $insertArsip = mysqli_query($conn, "INSERT INTO arsip(peserta_id, keterangan, tanggal_arsip) VALUES($peserta_id, '$keterangan', NOW())");
    $updatePeserta = mysqli_query($conn, "UPDATE peserta SET status='selesai' WHERE id=$peserta_id");
    if ($insertArsip && $updatePeserta) {
        $message = 'Peserta dengan ID ' . $peserta_id . ' telah diarsipkan otomatis!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengarsipkan peserta otomatis: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Handle new user registration
if (isset($_POST['register'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $universitas = mysqli_real_escape_string($conn, $_POST['universitas']);
    $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];

    $query = "INSERT INTO register (nama, email, password, nim, universitas, jurusan, no_hp, alamat, tanggal_masuk, tanggal_keluar, status, created_at, updated_at) 
              VALUES ('$nama', '$email', '$password', '$nim', '$universitas', '$jurusan', '$no_hp', '$alamat', '$tanggal_masuk', '$tanggal_keluar', 'pending', NOW(), NOW())";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Registrasi berhasil, menunggu verifikasi admin!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mendaftar: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Admin verification of new registrations with bidang selection
if (isset($_POST['verify_registration']) && $role == 'admin') {
    $register_id = (int)$_POST['register_id'];
    $bidang = mysqli_real_escape_string($conn, $_POST['bidang']);
    $register = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM register WHERE id = $register_id AND status = 'pending'"));
    
    if ($register) {
        $query = "INSERT INTO peserta (nama, email, telepon, institusi_id, user_id, alamat, tanggal_masuk, tanggal_keluar, status_verifikasi, foto, bidang) 
                  VALUES ('$register[nama]', '$register[email]', '$register[no_hp]', NULL, NULL, '$register[alamat]', '$register[tanggal_masuk]', '$register[tanggal_keluar]', 'verified', NULL, '$bidang')";
        
        if (mysqli_query($conn, $query)) {
            mysqli_query($conn, "DELETE FROM register WHERE id = $register_id");
            $message = 'Registrasi berhasil diverifikasi dan ditambahkan ke peserta dengan bidang ' . htmlspecialchars($bidang) . '!';
            $messageType = 'success';
        } else {
            $message = 'Gagal memverifikasi registrasi: ' . mysqli_error($conn);
            $messageType = 'error';
        }
    }
}

// Tambah peserta
if (isset($_POST['tambah']) && $role == 'admin') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $institusi_id = (int)$_POST['institusi_id'];
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $bidang = mysqli_real_escape_string($conn, $_POST['bidang']);
    $create_institusi = isset($_POST['create_institusi']) ? true : false;
    $institusi_nama = $create_institusi ? mysqli_real_escape_string($conn, $_POST['institusi_nama']) : '';

    if ($create_institusi && $institusi_nama) {
        $institusi_query = "INSERT INTO institusi (nama, created_at) VALUES ('$institusi_nama', NOW())";
        if (mysqli_query($conn, $institusi_query)) {
            $institusi_id = mysqli_insert_id($conn);
        } else {
            $message = 'Peserta berhasil ditambahkan, tapi gagal membuat institusi: ' . mysqli_error($conn);
            $messageType = 'warning';
        }
    }

    $query = "INSERT INTO peserta (nama, email, telepon, institusi_id, user_id, alamat, tanggal_masuk, tanggal_keluar, status_verifikasi, status, bidang) 
              VALUES ('$nama', '$email', '$telepon', '$institusi_id', NULL, '$alamat', '$tanggal_masuk', '$tanggal_keluar', 'verified', 'aktif', '$bidang')";
    
    if (mysqli_query($conn, $query)) {
        $peserta_id = mysqli_insert_id($conn);
        $foto_filename = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload_result = uploadFoto($_FILES['foto'], $peserta_id);
            if ($upload_result['success']) {
                $foto_filename = $upload_result['filename'];
                mysqli_query($conn, "UPDATE peserta SET foto = '$foto_filename' WHERE id = $peserta_id");
            }
        }
        $message = 'Peserta berhasil ditambahkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menambahkan peserta: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Tambah user untuk peserta
if (isset($_POST['tambah_user']) && $role == 'admin') {
    $peserta_id = (int)$_POST['peserta_id'];
    $user_nama = mysqli_real_escape_string($conn, $_POST['user_nama']);
    $user_email = mysqli_real_escape_string($conn, $_POST['user_email']);
    $user_password = password_hash($_POST['user_password'], PASSWORD_BCRYPT);
    $user_role = 'user';

    $user_query = "INSERT INTO users (nama, email, password, role, created_at) 
                   VALUES ('$user_nama', '$user_email', '$user_password', '$user_role', NOW())";
    if (mysqli_query($conn, $user_query)) {
        $user_id = mysqli_insert_id($conn);
        mysqli_query($conn, "UPDATE peserta SET user_id = $user_id WHERE id = $peserta_id");
        $message = 'Akun user berhasil dibuat untuk peserta!';
        $messageType = 'success';
    } else {
        $message = 'Gagal membuat akun user: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Update peserta
if (isset($_POST['update_peserta']) && $role == 'admin') {
    $peserta_id = (int)$_POST['peserta_id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $institusi_id = (int)$_POST['institusi_id'];
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $bidang = mysqli_real_escape_string($conn, $_POST['bidang']);

    $query = "UPDATE peserta SET 
              nama = '$nama', 
              email = '$email', 
              telepon = '$telepon', 
              institusi_id = $institusi_id, 
              alamat = '$alamat', 
              tanggal_masuk = '$tanggal_masuk',
              tanggal_keluar = '$tanggal_keluar',
              bidang = '$bidang'
              WHERE id = $peserta_id";
    
    if (mysqli_query($conn, $query)) {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload_result = uploadFoto($_FILES['foto'], $peserta_id);
            if ($upload_result['success']) {
                $foto_filename = $upload_result['filename'];
                mysqli_query($conn, "UPDATE peserta SET foto = '$foto_filename' WHERE id = $peserta_id");
            }
        }
        $message = 'Data peserta berhasil diupdate!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengupdate data: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}






// Pending Registrations Query
$pending_registrations_query = "SELECT * 
                                FROM register 
                                WHERE status = 'pending' 
                                AND (nama LIKE '%$search_peserta%' OR email LIKE '%$search_peserta%')
                                ORDER BY created_at DESC 
                                LIMIT $limit OFFSET $offset_peserta";
$pending_registrations = mysqli_query($conn, $pending_registrations_query);

// Total Pending Registrations untuk Pagination
$total_pending_query = "SELECT COUNT(*) as total 
                        FROM register 
                        WHERE status = 'pending' 
                        AND (nama LIKE '%$search_peserta%' OR email LIKE '%$search_peserta%')";
$total_pending_result = mysqli_fetch_assoc(mysqli_query($conn, $total_pending_query));
$total_pending_pages = ceil($total_pending_result['total'] / $limit);





// Arsip Query
$search_arsip = isset($_GET['search_arsip']) ? mysqli_real_escape_string($conn, $_GET['search_arsip']) : '';
$page_arsip = isset($_GET['page_arsip']) ? max(1, (int)$_GET['page_arsip']) : 1;
$offset_arsip = ($page_arsip - 1) * $limit;
$arsip_query = "SELECT a.*, p.nama, p.email, i.nama as institusi 
                FROM arsip a 
                JOIN peserta p ON a.peserta_id = p.id 
                LEFT JOIN institusi i ON p.institusi_id = i.id 
                WHERE p.nama LIKE '%$search_arsip%' OR p.email LIKE '%$search_arsip%' OR a.keterangan LIKE '%$search_arsip%'
                ORDER BY a.tanggal_arsip DESC 
                LIMIT $limit OFFSET $offset_arsip";
$arsip = mysqli_query($conn, $arsip_query);

// Total Arsip untuk Pagination
$total_arsip_query = "SELECT COUNT(*) as total 
                      FROM arsip a 
                      JOIN peserta p ON a.peserta_id = p.id 
                      WHERE p.nama LIKE '%$search_arsip%' OR p.email LIKE '%$search_arsip%' OR a.keterangan LIKE '%$search_arsip%'";
$total_arsip_result = mysqli_fetch_assoc(mysqli_query($conn, $total_arsip_query));
$total_arsip_pages = ceil($total_arsip_result['total'] / $limit);

// Query lainnya tetap sama
$users = mysqli_query($conn, "SELECT * FROM users WHERE role='user' ORDER BY nama");
$peserta_aktif = mysqli_query($conn, "SELECT * FROM peserta WHERE status='aktif' ORDER BY nama");
$peserta_no_user = mysqli_query($conn, "SELECT * FROM peserta WHERE user_id IS NULL AND status='aktif' ORDER BY nama");

// Handle manual archiving
if (isset($_GET['arsipkan']) && $role == 'admin') {
    $peserta_id = (int)$_GET['arsipkan'];
    $keterangan = mysqli_real_escape_string($conn, $_GET['keterangan'] ?? 'Selesai');
    
    $insertArsip = mysqli_query($conn, "INSERT INTO arsip(peserta_id, keterangan, tanggal_arsip) VALUES($peserta_id, '$keterangan', NOW())");
    $updatePeserta = mysqli_query($conn, "UPDATE peserta SET status='selesai' WHERE id=$peserta_id");
    
    if ($insertArsip && $updatePeserta) {
        $message = 'Peserta berhasil diarsipkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengarsipkan peserta: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Restore from archive
if (isset($_GET['restore']) && $role == 'admin') {
    $arsip_id = (int)$_GET['restore'];
    $arsipData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT peserta_id FROM arsip WHERE id = $arsip_id"));
    
    if ($arsipData) {
        $peserta_id = $arsipData['peserta_id'];
        $deleteArsip = mysqli_query($conn, "DELETE FROM arsip WHERE id = $arsip_id");
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

// Delete archive permanently
if (isset($_GET['hapus_arsip']) && $role == 'admin') {
    $arsip_id = (int)$_GET['hapus_arsip'];
    
    if (mysqli_query($conn, "DELETE FROM arsip WHERE id = $arsip_id")) {
        $message = 'Data arsip berhasil dihapus!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menghapus data arsip: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// CRUD Institusi
if (isset($_POST['tambah_institusi']) && $role == 'admin') {
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

if (isset($_POST['update_institusi']) && $role == 'admin') {
    $id = (int)$_POST['institusi_id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_institusi']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat_institusi']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon_institusi']);
    
    $query = "UPDATE institusi SET nama='$nama', alamat='$alamat', telepon='$telepon' WHERE id=$id";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Institusi berhasil diupdate!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengupdtae institusi: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

if (isset($_GET['hapus_institusi']) && $role == 'admin') {
    $id = (int)$_GET['hapus_institusi'];
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
$institusi = mysqli_query($conn, $institusi_query);
$users = mysqli_query($conn, "SELECT * FROM users WHERE role='user' ORDER BY nama");
$pending_registrations = mysqli_query($conn, $pending_registrations_query);
$arsip = mysqli_query($conn, "SELECT a.*, p.nama, p.email, i.nama as institusi 
                             FROM arsip a 
                             JOIN peserta p ON a.peserta_id = p.id 
                             LEFT JOIN institusi i ON p.institusi_id = i.id 
                             ORDER BY a.tanggal_arsip DESC");
$peserta_aktif = mysqli_query($conn, "SELECT * FROM peserta WHERE status='aktif' ORDER BY nama");
$peserta_no_user = mysqli_query($conn, "SELECT * FROM peserta WHERE user_id IS NULL AND status='aktif' ORDER BY nama");

// Get statistics
$total_peserta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta"))['total'];
$peserta_verified = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'verified'"))['total'];
$peserta_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'pending'"))['total'];
$peserta_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'rejected'"))['total'];
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
    <title>Data Peserta - <?= ucfirst($role) ?></title>
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

        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
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

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        /* Styling untuk Tabel */
.table-container {
    overflow-x: auto;
    max-width: 100%;
    -webkit-overflow-scrolling: touch;
}

.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.8rem; /* Ukuran font lebih kecil untuk kompak */
    table-layout: auto;
}

.table th,
.table td {
    padding: 0.4rem 0.6rem; /* Kurangi padding untuk kompak */
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px; /* Lebar maksimum kolom lebih kecil */
}

.table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    font-size: 0.65rem; /* Kecilkan font header */
    letter-spacing: 0.05em;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table td[data-label="Aksi"] {
    max-width: none; /* Kolom Aksi tidak dibatasi lebarnya */
}

.table td.btn-group {
    display: flex;
    gap: 0.4rem; /* Kurangi jarak antar tombol */
    justify-content: flex-start;
    flex-wrap: nowrap;
}

.table td.btn-group .btn {
    padding: 0.3rem 0.6rem; /* Kecilkan tombol */
    font-size: 0.7rem; /* Kecilkan font tombol */
    min-width: 50px; /* Lebar minimum tombol lebih kecil */
    line-height: 1.2;
}

.table tbody tr:hover {
    background: #f9fafb;
}

@media (max-width: 768px) {
    .table-container {
        overflow-x: hidden; /* Nonaktifkan scroll horizontal di mobile */
    }

    .table {
        display: block;
        width: 100%;
    }

    .table thead {
        display: none; /* Sembunyikan header di mobile */
    }

    .table tbody,
    .table tr,
    .table td {
        display: block;
        width: 100%;
        box-sizing: border-box;
    }

    .table tr {
        margin-bottom: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.4rem;
    }

    .table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.3rem 0.5rem; /* Kurangi padding di mobile */
        border-bottom: none;
        font-size: 0.75rem; /* Kecilkan font di mobile */
        word-break: break-word;
        white-space: normal;
    }

    .table td:before {
        content: attr(data-label);
        font-weight: 600;
        color: #374151;
        flex: 0 0 30%; /* Kurangi lebar label untuk ruang lebih */
        margin-right: 0.5rem;
    }

    .table td.btn-group {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.3rem;
        padding: 0.4rem;
    }

    .table td.btn-group .btn {
        flex: 1 1 45%; /* Tombol dalam dua kolom */
        text-align: center;
        margin: 0.2rem;
        padding: 0.3rem;
        font-size: 0.7rem;
        min-width: 60px;
    }

    .table td[data-label="Email"],
    .table td[data-label="Alamat"],
    .table td[data-label="Bidang"] {
        max-width: none;
        text-overflow: clip;
    }
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
    background: var(--primary-color);
    color: white;
    border: 1px solid var(--primary-color);
}

.pagination a:hover {
    background: var(--accent-color);
    border-color: var(--accent-color);
}

.pagination span {
    background: #e5e7eb;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

/* Styling untuk Search Bar */
.search-container {
    margin-bottom: 1.5rem;
    display: flex;
    gap: 0.5rem;
    max-width: 400px;
}

.search-container input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
}

.search-container button {
    padding: 0.75rem;
    border-radius: 8px;
    background: var(--primary-color);
    color: white;
    border: none;
    cursor: pointer;
}

.search-container button:hover {
    background: var(--accent-color);
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

        .status-rejected {
            background: #fecaca;
            color: #991b1b;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #fecaca;
            color: #991b1b;
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

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-color: #f59e0b;
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

        .modal-content-wide {
            max-width: 800px;
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

        .collapse-section {
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .collapse-header {
            padding: 0.75rem 1rem;
            background: #f9fafb;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .collapse-content {
            padding: 1rem;
            display: none;
        }

        .collapse-content.active {
            display: block;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
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

        .action-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .action-btn:hover {
            background: var(--secondary-color);
            border-color: var(--primary-color);
        }

        .section-content {
            display: none;
        }

        .section-content.active {
            display: block;
        }

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

        .col-md-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
            padding: 0 0.75rem;
        }

        .col-md-12 {
            flex: 0 0 100%;
            max-width: 100%;
            padding: 0 0.75rem;
        }

        @media (max-width: 768px) {
            .header-section h1 {
                font-size: 1.5rem;
            }
            .header-section p {
                font-size: 0.9rem;
            }
            .stats-grid {
                grid-template-columns: 1fr; /* Stack cards vertically on mobile */
            }
            .stats-card {
                margin-bottom: 1rem;
            }
            .col-md-4, .col-md-6, .col-md-8, .col-md-12 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .btn-group {
                flex-direction: column;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <button class="hamburger" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <nav class="sidebar" id="sidebar">
            <div class="user-profile">
                <div class="user-avatar"><?= $roleIcons[$role] ?></div>
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
                    $active = strpos($href, 'peserta.php') !== false ? 'active' : '';
                    echo "<div class='nav-item'><a class='nav-link $active' href='$href'><i class='$icon'></i> $title</a></div>";
                }
                ?>
                <div class="nav-item logout-link"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
            </div>
        </nav>

        <main class="main-content">
            <div class="header-section">
                <h1><i class="fas fa-users"></i> Data Peserta</h1>
                <p>Kelola data peserta, verifikasi, dan arsip magang</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <button class="action-btn <?= isset($_GET['tab']) && $_GET['tab'] == 'verifikasi' ? 'active' : '' ?>" onclick="showSection('verifikasi')"><i class="fas fa-check-circle"></i> Verifikasi & Daftar Peserta</button>
                <button class="action-btn <?= isset($_GET['tab']) && $_GET['tab'] == 'tambah_peserta' ? 'active' : '' ?>" onclick="showSection('tambah_peserta')"><i class="fas fa-user-plus"></i> Tambah Peserta</button>
                <button class="action-btn <?= isset($_GET['tab']) && $_GET['tab'] == 'tambah_user' ? 'active' : '' ?>" onclick="showSection('tambah_user')"><i class="fas fa-user-plus"></i> Tambah Akun User</button>
                <button class="action-btn <?= isset($_GET['tab']) && $_GET['tab'] == 'tambah_institusi' ? 'active' : '' ?>" onclick="showSection('tambah_institusi')"><i class="fas fa-building"></i> Tambah Institusi</button>
                <button class="action-btn <?= isset($_GET['tab']) && $_GET['tab'] == 'daftar_institusi' ? 'active' : '' ?>" onclick="showSection('daftar_institusi')"><i class="fas fa-list"></i> Daftar Institusi</button>
                <button class="action-btn <?= isset($_GET['tab']) && $_GET['tab'] == 'arsip' ? 'active' : '' ?>" onclick="showSection('arsip')"><i class="fas fa-archive"></i> Arsip Peserta</button>
            </div>

            <div id="verifikasi-section" class="section-content <?= isset($_GET['tab']) && $_GET['tab'] == 'verifikasi' ? 'active' : '' ?>">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-bell"></i> Registrasi Menunggu Verifikasi</h3>
                        </div>
                        <div class="card-body">
                            <div class="search-container">
                                <form method="get" id="searchVerifikasiForm">
                                    <input type="hidden" name="tab" value="verifikasi">
                                    <input type="text" name="search_peserta" id="searchVerifikasi" placeholder="Cari nama, email, atau bidang..." value="<?= htmlspecialchars($search_peserta) ?>">
                                    <button type="submit"><i class="fas fa-search"></i> Cari</button>
                                </form>
                            </div>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Tanggal Masuk</th>
                                            <th>Tanggal Keluar</th>
                                            <th>Bidang</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="verifikasiTable">
                                        <?php 
                                        $no = $offset_peserta + 1;
                                        while($r = mysqli_fetch_assoc($pending_registrations)): ?>
                                        <tr>
                                            <td data-label="No"><?= $no++ ?></td>
                                            <td data-label="Nama"><?= htmlspecialchars($r['nama']) ?></td>
                                            <td data-label="Email"><?= htmlspecialchars($r['email']) ?></td>
                                            <td data-label="Tanggal Masuk"><?= date('d/m/Y', strtotime($r['tanggal_masuk'])) ?></td>
                                            <td data-label="Tanggal Keluar"><?= date('d/m/Y', strtotime($r['tanggal_keluar'])) ?></td>
                                            <td data-label="Bidang">
                                                <form method="post" id="verifyForm_<?= $r['id'] ?>">
                                                    <input type="hidden" name="register_id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="tab" value="verifikasi">
                                                    <select name="bidang" class="form-control" required>
                                                        <option value="" disabled selected>Pilih Bidang</option>
                                                        <?php foreach($bidang_options as $bidang): ?>
                                                            <option value="<?= $bidang ?>"><?= $bidang ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                            </td>
                                            <td data-label="Aksi">
                                                <button type="submit" name="verify_registration" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Verifikasi
                                                </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($pending_registrations) == 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <div style="padding: 2rem;">
                                                    <i class="fas fa-bell" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                                                    <p>Tidak ada registrasi pending</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination">
                                <?php if ($page_peserta > 1): ?>
                                    <a href="?tab=verifikasi&page_peserta=<?= $page_peserta - 1 ?>&search_peserta=<?= urlencode($search_peserta) ?>"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
                                <?php else: ?>
                                    <span><i class="fas fa-chevron-left"></i> Sebelumnya</span>
                                <?php endif; ?>
                                <span>Halaman <?= $page_peserta ?> dari <?= $total_pending_pages ?></span>
                                <?php if ($page_peserta < $total_pending_pages): ?>
                                    <a href="?tab=verifikasi&page_peserta=<?= $page_peserta + 1 ?>&search_peserta=<?= urlencode($search_peserta) ?>">Selanjutnya <i class="fas fa-chevron-right"></i></a>
                                <?php else: ?>
                                    <span>Selanjutnya <i class="fas fa-chevron-right"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Daftar Peserta Aktif</h3>
                    </div>
                    <div class="card-body">
                        <div class="search-container">
                            <form method="get" id="searchPesertaForm">
                                <input type="hidden" name="tab" value="verifikasi">
                                <input type="text" name="search_peserta" id="searchPeserta" placeholder="Cari nama, email, atau bidang..." value="<?= htmlspecialchars($search_peserta) ?>">
                                <button type="submit"><i class="fas fa-search"></i> Cari</button>
                            </form>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Institusi</th>
                                        <th>Bidang</th>
                                        <th>Status Magang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="pesertaTable">
                                    <?php 
                                    $no = $offset_peserta + 1;
                                    while($p = mysqli_fetch_assoc($peserta)): 
                                        $today = date('Y-m-d');
                                        $status_magang = (strtotime($p['tanggal_keluar']) < strtotime($today)) ? 'completed' : 'active';
                                    ?>
                                    <tr>
                                        <td data-label="No"><?= $no++ ?></td>
                                        <td data-label="Nama"><strong><?= htmlspecialchars($p['nama']) ?></strong></td>
                                        <td data-label="Institusi"><?= htmlspecialchars($p['institusi'] ?? 'Tidak ada institusi') ?></td>
                                        <td data-label="Bidang"><?= htmlspecialchars($p['bidang'] ?? '-') ?></td>
                                        <td data-label="Status Magang">
                                            <span class="status-badge status-<?= $status_magang ?>">
                                                <?= ucfirst($status_magang) ?>
                                            </span>
                                        </td>
                                        <td data-label="Aksi">
    <div class="btn-group">
        <button class="btn btn-sm btn-primary" data-action="view" data-id="<?= $p['id'] ?>" title="Lihat Detail">
            <i class="fas fa-eye"></i>
        </button>
        <?php if ($role == 'admin'): ?>
            <button class="btn btn-sm btn-success" data-action="edit" data-id="<?= $p['id'] ?>" title="Edit">
    <i class="fas fa-edit"></i>
</button>
            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama']) ?>')" title="Hapus">
                <i class="fas fa-trash"></i>
            </button>
            <?php if ($status_magang == 'active'): ?>
                <a href="?tab=verifikasi&arsipkan=<?= $p['id'] ?>&keterangan=Selesai" class="btn btn-sm btn-warning" onclick="return confirm('Yakin ingin mengarsipkan peserta \"<?= htmlspecialchars($p['nama']) ?>\" dengan keterangan \"Selesai\"?')">
                    <i class="fas fa-archive"></i>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if (mysqli_num_rows($peserta) == 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div style="padding: 2rem;">
                                                <i class="fas fa-users" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                                                <p>Belum ada data peserta</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination">
                            <?php if ($page_peserta > 1): ?>
                                <a href="?tab=verifikasi&page_peserta=<?= $page_peserta - 1 ?>&search_peserta=<?= urlencode($search_peserta) ?>"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
                            <?php else: ?>
                                <span><i class="fas fa-chevron-left"></i> Sebelumnya</span>
                            <?php endif; ?>
                            <span>Halaman <?= $page_peserta ?> dari <?= $total_peserta_pages ?></span>
                            <?php if ($page_peserta < $total_peserta_pages): ?>
                                <a href="?tab=verifikasi&page_peserta=<?= $page_peserta + 1 ?>&search_peserta=<?= urlencode($search_peserta) ?>">Selanjutnya <i class="fas fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span>Selanjutnya <i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tambah_peserta-section" class="section-content <?= isset($_GET['tab']) && $_GET['tab'] == 'tambah_peserta' ? 'active' : '' ?>">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-plus"></i> Tambah Peserta Baru</h3>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="tab" value="tambah_peserta">
                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="institusi_id" class="form-label">Institusi</label>
                                    <select class="form-control" id="institusi_id" name="institusi_id">
                                        <option value="">Pilih Institusi</option>
                                        <?php
                                        $institusi_query = mysqli_query($conn, "SELECT * FROM institusi ORDER BY nama");
                                        while ($inst = mysqli_fetch_assoc($institusi_query)):
                                        ?>
                                            <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['nama']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="bidang" class="form-label">Bidang</label>
                                    <select class="form-control" id="bidang" name="bidang" required>
                                        <option value="" disabled selected>Pilih Bidang</option>
                                        <?php foreach($bidang_options as $bidang): ?>
                                            <option value="<?= $bidang ?>"><?= $bidang ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                                    <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" required>
                                </div>
                                <div class="mb-3">
                                    <label for="tanggal_keluar" class="form-label">Tanggal Keluar</label>
                                    <input type="date" class="form-control" id="tanggal_keluar" name="tanggal_keluar" required>
                                </div>
                                <div class="mb-3">
                                    <label for="foto" class="form-label">Foto</label>
                                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                                </div>
                                <button type="submit" name="tambah" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tambah Peserta
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tambah_user-section" class="section-content <?= isset($_GET['tab']) && $_GET['tab'] == 'tambah_user' ? 'active' : '' ?>">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-plus"></i> Tambah Akun User untuk Peserta</h3>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="tab" value="tambah_user">
                                <div class="mb-3">
                                    <label for="peserta_id" class="form-label">Pilih Peserta</label>
                                    <select class="form-control" id="peserta_id" name="peserta_id" required>
                                        <option value="" disabled selected>Pilih Peserta</option>
                                        <?php
                                        $peserta_query = mysqli_query($conn, "SELECT id, nama FROM peserta WHERE status = 'aktif' ORDER BY nama");
                                        while ($pes = mysqli_fetch_assoc($peserta_query)):
                                        ?>
                                            <option value="<?= $pes['id'] ?>"><?= htmlspecialchars($pes['nama']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="user">User</option>
                                        <option value="pembimbing">Pembimbing</option>
                                    </select>
                                </div>
                                <button type="submit" name="tambah_user" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tambah Akun User
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tambah_institusi-section" class="section-content <?= isset($_GET['tab']) && $_GET['tab'] == 'tambah_institusi' ? 'active' : '' ?>">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-building"></i> Tambah Institusi Baru</h3>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="tab" value="tambah_institusi">
                                <div class="mb-3">
                                    <label for="nama_institusi" class="form-label">Nama Institusi</label>
                                    <input type="text" class="form-control" id="nama_institusi" name="nama" required>
                                </div>
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="telepon" class="form-label">Telepon</label>
                                    <input type="text" class="form-control" id="telepon" name="telepon" required>
                                </div>
                                <button type="submit" name="tambah_institusi" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tambah Institusi
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="daftar_institusi-section" class="section-content <?= isset($_GET['tab']) && $_GET['tab'] == 'daftar_institusi' ? 'active' : '' ?>">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Daftar Institusi</h3>
                        </div>
                        <div class="card-body">
                            <div class="search-container">
                                <form method="get" id="searchInstitusiForm">
                                    <input type="hidden" name="tab" value="daftar_institusi">
                                    <input type="text" name="search_institusi" id="searchInstitusi" placeholder="Cari nama institusi..." value="<?= htmlspecialchars($search_institusi) ?>">
                                    <button type="submit"><i class="fas fa-search"></i> Cari</button>
                                </form>
                            </div>
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
                                    <tbody id="institusiTable">
                                        <?php 
                                        $no = $offset_institusi + 1;
                                        while($row = mysqli_fetch_assoc($institusi)): ?>
                                            <tr>
                                                <td data-label="No"><?= $no++ ?></td>
                                                <td data-label="Nama Institusi"><?= htmlspecialchars($row['nama']) ?></td>
                                                <td data-label="Alamat"><?= htmlspecialchars($row['alamat']) ?></td>
                                                <td data-label="Telepon"><?= htmlspecialchars($row['telepon']) ?></td>
                                                <td data-label="Aksi">
                                                    <div class="btn-group">
                                                        <button onclick="editInstitusi(<?= $row['id'] ?>, '<?= addslashes($row['nama']) ?>', '<?= addslashes($row['alamat']) ?>', '<?= addslashes($row['telepon']) ?>')" 
                                                                class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <a href="?tab=daftar_institusi&hapus_institusi=<?= $row['id'] ?>" 
                                                           class="btn btn-danger btn-sm"
                                                           onclick="return confirm('Yakin ingin menghapus institusi ini?')">
                                                            <i class="fas fa-trash"></i> Hapus
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
                            <div class="pagination">
                                <?php if ($page_institusi > 1): ?>
                                    <a href="?tab=daftar_institusi&page_institusi=<?= $page_institusi - 1 ?>&search_institusi=<?= urlencode($search_institusi) ?>"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
                                <?php else: ?>
                                    <span><i class="fas fa-chevron-left"></i> Sebelumnya</span>
                                <?php endif; ?>
                                <span>Halaman <?= $page_institusi ?> dari <?= $total_institusi_pages ?></span>
                                <?php if ($page_institusi < $total_institusi_pages): ?>
                                    <a href="?tab=daftar_institusi&page_institusi=<?= $page_institusi + 1 ?>&search_institusi=<?= urlencode($search_institusi) ?>">Selanjutnya <i class="fas fa-chevron-right"></i></a>
                                <?php else: ?>
                                    <span>Selanjutnya <i class="fas fa-chevron-right"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="arsip-section" class="section-content <?= isset($_GET['tab']) && $_GET['tab'] == 'arsip' ? 'active' : '' ?>">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-archive"></i> Arsipkan Peserta</h3>
                        </div>
                        <div class="card-body">
                            <form method="get">
                                <input type="hidden" name="tab" value="arsip">
                                <div class="mb-3">
                                    <label for="peserta_arsip" class="form-label">Pilih Peserta</label>
                                    <select class="form-control" id="peserta_arsip" name="arsipkan" required>
                                        <option value="" disabled selected>Pilih Peserta</option>
                                        <?php
                                        $peserta_arsip_query = mysqli_query($conn, "SELECT id, nama FROM peserta WHERE status = 'aktif' ORDER BY nama");
                                        while ($pes = mysqli_fetch_assoc($peserta_arsip_query)):
                                        ?>
                                            <option value="<?= $pes['id'] ?>"><?= htmlspecialchars($pes['nama']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="keterangan" class="form-label">Keterangan</label>
                                    <input type="text" class="form-control" id="keterangan" name="keterangan" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-archive"></i> Arsipkan Peserta
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Daftar Arsip Peserta</h3>
                    </div>
                    <div class="card-body">
                        <div class="search-container">
                            <form method="get" id="searchArsipForm">
                                <input type="hidden" name="tab" value="arsip">
                                <input type="text" name="search_arsip" id="searchArsip" placeholder="Cari nama, email, atau keterangan..." value="<?= htmlspecialchars($search_arsip) ?>">
                                <button type="submit"><i class="fas fa-search"></i> Cari</button>
                            </form>
                        </div>
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
                                <tbody id="arsipTable">
                                    <?php 
                                    $no = $offset_arsip + 1;
                                    while($row = mysqli_fetch_assoc($arsip)): ?>
                                        <tr>
                                            <td data-label="No"><?= $no++ ?></td>
                                            <td data-label="Nama Peserta"><?= htmlspecialchars($row['nama']) ?></td>
                                            <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                                            <td data-label="Institusi"><?= htmlspecialchars($row['institusi'] ?? 'Tidak ada') ?></td>
                                            <td data-label="Keterangan">
                                                <span class="badge bg-secondary"><?= htmlspecialchars($row['keterangan']) ?></span>
                                            </td>
                                            <td data-label="Tanggal Arsip"><?= date('d/m/Y H:i', strtotime($row['tanggal_arsip'])) ?></td>
                                            <td data-label="Aksi">
                                                <div class="btn-group">
                                                    <?php if ($role == 'admin'): ?>
                                                        <a href="?tab=arsip&restore=<?= $row['id'] ?>" 
                                                           class="btn btn-success btn-sm"
                                                           onclick="return confirm('Yakin ingin mengembalikan peserta ini dari arsip?')">
                                                            <i class="fas fa-undo"></i> Restore
                                                        </a>
                                                        <a href="?tab=arsip&hapus_arsip=<?= $row['id'] ?>" 
                                                           class="btn btn-danger btn-sm"
                                                           onclick="return confirm('Yakin ingin menghapus data arsip ini secara permanen?')">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </a>
                                                    <?php endif; ?>
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
                        <div class="pagination">
                            <?php if ($page_arsip > 1): ?>
                                <a href="?tab=arsip&page_arsip=<?= $page_arsip - 1 ?>&search_arsip=<?= urlencode($search_arsip) ?>"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
                            <?php else: ?>
                                <span><i class="fas fa-chevron-left"></i> Sebelumnya</span>
                            <?php endif; ?>
                            <span>Halaman <?= $page_arsip ?> dari <?= $total_arsip_pages ?></span>
                            <?php if ($page_arsip < $total_arsip_pages): ?>
                                <a href="?tab=arsip&page_arsip=<?= $page_arsip + 1 ?>&search_arsip=<?= urlencode($search_arsip) ?>">Selanjutnya <i class="fas fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span>Selanjutnya <i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

<div id="editModal" class="modal">
    <div class="modal-content modal-content-wide">
        <div class="modal-header">
            <h4>Edit Data Peserta</h4>
            <span class="close" onclick="closeModal('editModal')">×</span>
        </div>
        <div class="modal-body">
            <form method="post" id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : 'verifikasi' ?>">
                <input type="hidden" name="peserta_id" id="edit_peserta_id">
                <div class="mb-3">
                    <label for="edit_nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="edit_nama" name="nama" required>
                </div>
                <div class="mb-3">
                    <label for="edit_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="edit_email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="edit_telepon" class="form-label">Telepon</label>
                    <input type="text" class="form-control" id="edit_telepon" name="telepon">
                </div>
                <div class="mb-3">
                    <label for="edit_alamat" class="form-label">Alamat</label>
                    <textarea class="form-control" id="edit_alamat" name="alamat"></textarea>
                </div>
                <div class="mb-3">
                    <label for="edit_institusi_id" class="form-label">Institusi</label>
                    <select class="form-control" id="edit_institusi_id" name="institusi_id">
                        <option value="">Pilih Institusi</option>
                        <?php
                        $institusi_query = mysqli_query($conn, "SELECT * FROM institusi ORDER BY nama");
                        while ($inst = mysqli_fetch_assoc($institusi_query)):
                        ?>
                            <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['nama']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_bidang" class="form-label">Bidang</label>
                    <select class="form-control" id="edit_bidang" name="bidang" required>
                        <option value="" disabled>Pilih Bidang</option>
                        <?php foreach($bidang_options as $bidang): ?>
                            <option value="<?= $bidang ?>"><?= $bidang ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_tanggal_masuk" class="form-label">Tanggal Masuk</label>
                    <input type="date" class="form-control" id="edit_tanggal_masuk" name="tanggal_masuk" required>
                </div>
                <div class="mb-3">
                    <label for="edit_tanggal_keluar" class="form-label">Tanggal Keluar</label>
                    <input type="date" class="form-control" id="edit_tanggal_keluar" name="tanggal_keluar" required>
                </div>
                <div class="mb-3">
                    <label for="edit_foto" class="form-label">Foto Baru (Kosongkan jika tidak ingin mengganti)</label>
                    <input type="file" class="form-control" id="edit_foto" name="foto" accept="image/*">
                    <img id="editFotoPreview" style="display:none; max-width:150px; margin-top:10px;">
                    <div id="editFotoPlaceholder" style="display:block;">Tidak ada foto</div>
                </div>
                <button type="submit" name="update_peserta" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>

            <div id="detailModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4>Detail Peserta</h4>
                        <span class="close" onclick="closeModal('detailModal')">×</span>
                    </div>
                    <div class="modal-body">
                        <div id="detailContent"></div>
                    </div>
                </div>
            </div>

            <div id="editInstitusiModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4>Edit Institusi</h4>
                        <span class="close" onclick="closeModal('editInstitusiModal')">×</span>
                    </div>
                    <div class="modal-body">
                        <form method="post">
                            <input type="hidden" name="tab" value="daftar_institusi">
                            <input type="hidden" name="institusi_id" id="edit_institusi_id">
                            <div class="mb-3">
                                <label for="edit_nama_institusi" class="form-label">Nama Institusi</label>
                                <input type="text" class="form-control" id="edit_nama_institusi" name="nama" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_alamat_institusi" class="form-label">Alamat</label>
                                <textarea class="form-control" id="edit_alamat_institusi" name="alamat" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_telepon_institusi" class="form-label">Telepon</label>
                                <input type="text" class="form-control" id="edit_telepon_institusi" name="telepon" required>
                            </div>
                            <button type="submit" name="update_institusi" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    sidebar.classList.toggle('hidden');
    mainContent.style.marginLeft = sidebar.classList.contains('hidden') ? '0' : '280px';
}

function showSection(sectionId) {
    document.querySelectorAll('.section-content').forEach(section => section.classList.remove('active'));
    document.getElementById(sectionId + '-section').classList.add('active');
    document.querySelectorAll('.action-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[onclick="showSection('${sectionId}')"]`).classList.add('active');
    history.pushState(null, null, `?tab=${sectionId}`);
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    if (modalId === 'editModal') {
        document.getElementById('editForm').reset();
        document.getElementById('editFotoPreview').style.display = 'none';
        document.getElementById('editFotoPlaceholder').style.display = 'block';
    }
}

function showDetail(id) {
    console.log('showDetail called with id:', id);
    $.ajax({
        url: 'api/get_peserta.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            console.log('Detail response:', data);
            if (data.error) {
                $('#detailContent').html('<p class="alert alert-error">Error: ' + data.error + '</p>');
                document.getElementById('detailModal').style.display = 'block';
                return;
            }
            const html = `
                <div class="text-center mb-3">
                    <img src="${data.foto ? 'Uploads/peserta/' + data.foto : 'https://via.placeholder.com/150'}" alt="Foto Peserta" style="width:150px; height:150px; border-radius:8px; object-fit:cover;">
                </div>
                <p><strong>Nama:</strong> ${data.nama || '-'}</p>
                <p><strong>Email:</strong> ${data.email || '-'}</p>
                <p><strong>Telepon:</strong> ${data.telepon || '-'}</p>
                <p><strong>Institusi:</strong> ${data.institusi || 'Tidak ada'}</p>
                <p><strong>Bidang:</strong> ${data.bidang || '-'}</p>
                <p><strong>Tanggal Masuk:</strong> ${data.tanggal_masuk ? new Date(data.tanggal_masuk).toLocaleDateString('id-ID') : '-'}</p>
                <p><strong>Tanggal Keluar:</strong> ${data.tanggal_keluar ? new Date(data.tanggal_keluar).toLocaleDateString('id-ID') : '-'}</p>
                <p><strong>Alamat:</strong> ${data.alamat || '-'}</p>
                <p><strong>Status Verifikasi:</strong> ${data.status_verifikasi || '-'}</p>
                <p><strong>Status Magang:</strong> ${data.status || '-'}</p>
            `;
            $('#detailContent').html(html);
            document.getElementById('detailModal').style.display = 'block';
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Detail AJAX error:', textStatus, errorThrown, 'Status:', jqXHR.status, 'Response:', jqXHR.responseText);
            $('#detailContent').html('<p class="alert alert-error">Gagal memuat data peserta: ' + textStatus + '</p>');
            document.getElementById('detailModal').style.display = 'block';
        }
    });
}

function showEditModal(id) {
    console.log('showEditModal called with id:', id);
    $.ajax({
        url: 'api/get_peserta.php',
        method: 'GET',
        data: { id: id, action: 'edit' },
        dataType: 'json',
        success: function(data) {
            console.log('Edit response:', data);
            if (data.error) {
                alert('Error: ' + data.error);
                return;
            }
            $('#edit_peserta_id').val(data.id || '');
            $('#edit_nama').val(data.nama || '');
            $('#edit_email').val(data.email || '');
            $('#edit_telepon').val(data.telepon || '');
            $('#edit_institusi_id').val(data.institusi_id || '');
            $('#edit_bidang').val(data.bidang || '');
            $('#edit_tanggal_masuk').val(data.tanggal_masuk || '');
            $('#edit_tanggal_keluar').val(data.tanggal_keluar || '');
            $('#edit_alamat').val(data.alamat || '');
            const editFotoPreview = document.getElementById('editFotoPreview');
            const editFotoPlaceholder = document.getElementById('editFotoPlaceholder');
            if (data.foto) {
                editFotoPreview.src = 'Uploads/peserta/' + data.foto;
                editFotoPreview.style.display = 'block';
                editFotoPlaceholder.style.display = 'none';
            } else {
                editFotoPreview.style.display = 'none';
                editFotoPlaceholder.style.display = 'block';
            }
            document.getElementById('editModal').style.display = 'block';
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Edit AJAX error:', textStatus, errorThrown, 'Status:', jqXHR.status, 'Response:', jqXHR.responseText);
            alert('Gagal memuat data peserta: ' + textStatus + ' (' + errorThrown + '). Cek console untuk detail.');
        }
    });
}

$(document).on('click', '.btn-success[data-action="edit"]', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    console.log('Edit button clicked, ID:', id);
    showEditModal(id);
});

$(document).on('click', '.btn-primary[data-action="view"]', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    console.log('View button clicked, ID:', id);
    showDetail(id);
});

function editInstitusi(id, nama, alamat, telepon) {
    $('#edit_institusi_id').val(id);
    $('#edit_nama_institusi').val(nama);
    $('#edit_alamat_institusi').val(alamat);
    $('#edit_telepon_institusi').val(telepon);
    document.getElementById('editInstitusiModal').style.display = 'block';
}

function confirmDelete(id, nama) {
    if (confirm(`Yakin ingin menghapus peserta "${nama}"?`)) {
        window.location.href = `?tab=verifikasi&hapus=${id}`;
    }
}

$(document).ready(function() {
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function performSearch(input, tableId, url, tab) {
        const searchTerm = input.val().trim();
        $.ajax({
            url: url,
            method: 'GET',
            data: { 
                search: searchTerm, 
                tab: tab,
                page: 1
            },
            success: function(data) {
                $(`#${tableId}`).html($(data).find(`#${tableId}`).html());
                const pagination = $(data).find('.pagination').html();
                $(`#${tableId}`).closest('.card-body').find('.pagination').html(pagination);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Search error:', textStatus, errorThrown);
                $(`#${tableId}`).html(`<tr><td colspan="100">Gagal memuat data: ${textStatus} (${errorThrown}). Silakan coba lagi.</td></tr>`);
            }
        });
    }

    const debouncedSearchVerifikasi = debounce(function() {
        performSearch($('#searchVerifikasi'), 'verifikasiTable', 'api/search_verifikasi.php', 'verifikasi');
    }, 300);

    const debouncedSearchPeserta = debounce(function() {
        performSearch($('#searchPeserta'), 'pesertaTable', 'api/search_peserta.php', 'verifikasi');
    }, 300);

    const debouncedSearchInstitusi = debounce(function() {
        performSearch($('#searchInstitusi'), 'institusiTable', 'api/search_institusi.php', 'daftar_institusi');
    }, 300);

    const debouncedSearchArsip = debounce(function() {
        performSearch($('#searchArsip'), 'arsipTable', 'api/search_arsip.php', 'arsip');
    }, 300);

    $('#searchVerifikasi').on('input', debouncedSearchVerifikasi);
    $('#searchPeserta').on('input', debouncedSearchPeserta);
    $('#searchInstitusi').on('input', debouncedSearchInstitusi);
    $('#searchArsip').on('input', debouncedSearchArsip);

    const urlParams = new URLSearchParams(window.location.search);
    const activeSection = urlParams.get('tab') || 'verifikasi';
    showSection(activeSection);

    function setupFotoPreview(inputId, previewId, placeholderId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const placeholder = document.getElementById(placeholderId);

        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'block';
            }
        });
    }

    setupFotoPreview('foto', 'fotoPreview', 'fotoPlaceholder');
    setupFotoPreview('edit_foto', 'editFotoPreview', 'editFotoPlaceholder');
});
</script>
</body>
</html>