<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

// Initialize variables
$message = '';
$messageType = '';

$role = $_SESSION['user']['role'];
$userName = $role === 'user' ? ($_SESSION['user']['nama'] ?? 'User') : ucfirst($role);

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
        // Insert ke users dengan nama dan email dari register
        $user_nama = $register['nama'];
        $user_email = $register['email'];
        $user_password = $register['password'];  // Sudah hashed
        $user_role = 'user';  // Default role untuk peserta

        $insert_user = "INSERT INTO users (nama, email, password, role, created_at) 
                        VALUES ('$user_nama', '$user_email', '$user_password', '$user_role', NOW())";
        
        if (mysqli_query($conn, $insert_user)) {
            $user_id = mysqli_insert_id($conn);
            
            $query = "INSERT INTO peserta (nama, email, telepon, institusi_id, user_id, alamat, tanggal_masuk, tanggal_keluar, status_verifikasi, foto, bidang) 
                      VALUES ('$register[nama]', '$register[email]', '$register[no_hp]', NULL, $user_id, '$register[alamat]', '$register[tanggal_masuk]', '$register[tanggal_keluar]', 'verified', NULL, '$bidang')";
            
            if (mysqli_query($conn, $query)) {
                mysqli_query($conn, "DELETE FROM register WHERE id = $register_id");
                $message = 'Registrasi berhasil diverifikasi dan ditambahkan ke peserta dengan bidang ' . htmlspecialchars($bidang) . '!';
                $messageType = 'success';
            } else {
                $message = 'Gagal memverifikasi registrasi: ' . mysqli_error($conn);
                $messageType = 'error';
                // Rollback insert users jika gagal
                mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
            }
        } else {
            $message = 'Gagal membuat akun user: ' . mysqli_error($conn);
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
    
    if ($create_institusi && !empty($institusi_nama)) {
        $institusi_alamat = mysqli_real_escape_string($conn, $_POST['institusi_alamat']);
        $institusi_telepon = mysqli_real_escape_string($conn, $_POST['institusi_telepon']);
        $insert_institusi = mysqli_query($conn, "INSERT INTO institusi (nama, alamat, telepon) VALUES ('$institusi_nama', '$institusi_alamat', '$institusi_telepon')");
        if ($insert_institusi) {
            $institusi_id = mysqli_insert_id($conn);
        } else {
            $message = 'Gagal menambahkan institusi: ' . mysqli_error($conn);
            $messageType = 'error';
            goto end_add_peserta;
        }
    }

    $foto = $_FILES['foto'];
    if ($foto['size'] > 0) {
        $upload_result = uploadFoto($foto, 0); // 0 temporary for now
        if (!$upload_result['success']) {
            $message = $upload_result['message'];
            $messageType = 'error';
            goto end_add_peserta;
        }
        $foto_filename = $upload_result['filename'];
    } else {
        $foto_filename = '';
    }

    $query = "INSERT INTO peserta (nama, email, telepon, institusi_id, alamat, tanggal_masuk, tanggal_keluar, bidang, foto, status_verifikasi, status, created_at, updated_at) 
              VALUES ('$nama', '$email', '$telepon', $institusi_id, '$alamat', '$tanggal_masuk', '$tanggal_keluar', '$bidang', '$foto_filename', 'verified', 'aktif', NOW(), NOW())";
    
    if (mysqli_query($conn, $query)) {
        $peserta_id = mysqli_insert_id($conn);
        // Update filename with actual ID
        if ($foto_filename) {
            $old_path = "Uploads/peserta/" . $foto_filename;
            $new_filename = "peserta_" . $peserta_id . "_" . time() . "." . pathinfo($foto_filename, PATHINFO_EXTENSION);
            $new_path = "Uploads/peserta/" . $new_filename;
            rename($old_path, $new_path);
            mysqli_query($conn, "UPDATE peserta SET foto = '$new_filename' WHERE id = $peserta_id");
        }
        $message = 'Peserta berhasil ditambahkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menambahkan peserta: ' . mysqli_error($conn);
        $messageType = 'error';
        if ($foto_filename) {
            unlink("Uploads/peserta/" . $foto_filename);
        }
    }
    end_add_peserta:
}

// Edit peserta
if (isset($_POST['edit']) && $role == 'admin') {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $institusi_id = (int)$_POST['institusi_id'];
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $bidang = mysqli_real_escape_string($conn, $_POST['bidang']);
    
    $foto = $_FILES['foto'];
    $foto_update = '';
    if ($foto['size'] > 0) {
        $upload_result = uploadFoto($foto, $id);
        if (!$upload_result['success']) {
            $message = $upload_result['message'];
            $messageType = 'error';
            goto end_edit_peserta;
        }
        $foto_update = ", foto = '" . $upload_result['filename'] . "'";
    }

    $query = "UPDATE peserta SET nama = '$nama', email = '$email', telepon = '$telepon', institusi_id = $institusi_id, alamat = '$alamat', tanggal_masuk = '$tanggal_masuk', tanggal_keluar = '$tanggal_keluar', bidang = '$bidang' $foto_update WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Peserta berhasil diupdate!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengupdate peserta: ' . mysqli_error($conn);
        $messageType = 'error';
    }
    end_edit_peserta:
}

// Hapus peserta
if (isset($_GET['hapus']) && $role == 'admin') {
    $id = (int)$_GET['hapus'];
    $peserta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto FROM peserta WHERE id = $id"));
    
    $query = "DELETE FROM peserta WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        if ($peserta['foto'] && file_exists("Uploads/peserta/" . $peserta['foto'])) {
            unlink("Uploads/peserta/" . $peserta['foto']);
        }
        $message = 'Peserta berhasil dihapus!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menghapus peserta: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Tambah institusi
if (isset($_POST['tambah_institusi']) && $role == 'admin') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    
    $query = "INSERT INTO institusi (nama, alamat, telepon) VALUES ('$nama', '$alamat', '$telepon')";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Institusi berhasil ditambahkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menambahkan institusi: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Edit institusi
if (isset($_POST['edit_institusi']) && $role == 'admin') {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    
    $query = "UPDATE institusi SET nama = '$nama', alamat = '$alamat', telepon = '$telepon' WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Institusi berhasil diupdate!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengupdate institusi: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Hapus institusi
if (isset($_GET['hapus_institusi']) && $role == 'admin') {
    $id = (int)$_GET['hapus_institusi'];
    
    $query = "DELETE FROM institusi WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Institusi berhasil dihapus!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menghapus institusi: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Arsip Query
$search_arsip = isset($_GET['search_arsip']) ? mysqli_real_escape_string($conn, $_GET['search_arsip']) : '';
$page_arsip = isset($_GET['page_arsip']) ? max(1, (int)$_GET['page_arsip']) : 1;
$offset_arsip = ($page_arsip - 1) * $limit;

$arsip_query = "SELECT a.*, p.nama as peserta_nama, p.bidang 
                FROM arsip a 
                LEFT JOIN peserta p ON a.peserta_id = p.id 
                WHERE p.nama LIKE '%$search_arsip%' OR a.keterangan LIKE '%$search_arsip%' OR p.bidang LIKE '%$search_arsip%'
                ORDER BY a.tanggal_arsip DESC 
                LIMIT $limit OFFSET $offset_arsip";
$arsip = mysqli_query($conn, $arsip_query);

// Total Arsip untuk Pagination
$total_arsip_query = "SELECT COUNT(*) as total 
                      FROM arsip a 
                      LEFT JOIN peserta p ON a.peserta_id = p.id 
                      WHERE p.nama LIKE '%$search_arsip%' OR a.keterangan LIKE '%$search_arsip%' OR p.bidang LIKE '%$search_arsip%'";
$total_arsip_result = mysqli_fetch_assoc(mysqli_query($conn, $total_arsip_query));
$total_arsip_pages = ceil($total_arsip_result['total'] / $limit);
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
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1E3A8A;
            --secondary-color: #EFF6FF;
            --accent-color: #60A5FA;
            --text-color: #1f2937;
            --muted-color: #6b7280;
            --success-color: #10b981;
            --danger-color: #ef4444;
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

        .tab-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab-button {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            background: #f3f4f6;
            color: var(--muted-color);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
        }

        .tab-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .card {
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

        .search-input {
            max-width: 400px;
            margin-bottom: 1rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f3f4f6;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-color);
        }

        .table td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
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
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2563EB;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background: #f3f4f6;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: var(--accent-color);
            color: white;
        }

        .pagination .active {
            background: var(--primary-color);
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            max-width: 500px;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative;
        }

        .close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            color: #6b7280;
            cursor: pointer;
        }

        .close:hover {
            color: var(--text-color);
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
            border-color: var(--success-color);
        }

        .alert-error {
            background: #fecaca;
            color: #991b1b;
            border-color: var(--danger-color);
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
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30,58,138,0.1);
        }

        #createInstitusiForm {
            margin-top: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
        }

        #createInstitusiForm.hidden {
            display: none;
        }

        .foto-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 0.5rem;
            display: none;
        }

        .foto-placeholder {
            width: 150px;
            height: 150px;
            background: #f3f4f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted-color);
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            .tab-buttons {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .card-body {
                padding: 1.5rem;
            }
            .table th, .table td {
                padding: 0.75rem;
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
                    $active = $href === 'peserta.php' ? 'active' : '';
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
                <h1><i class="fas fa-users"></i> Manajemen Data Peserta</h1>
                <p>Kelola data peserta, institusi, dan verifikasi pendaftaran</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="tab-buttons">
                <div class="tab-button active" onclick="showSection('verifikasi')"><i class="fas fa-check-circle"></i> Verifikasi Pendaftaran</div>
                <div class="tab-button" onclick="showSection('daftar_peserta')"><i class="fas fa-users"></i> Daftar Peserta</div>
                <div class="tab-button" onclick="showSection('daftar_institusi')"><i class="fas fa-building"></i> Daftar Institusi</div>
                <div class="tab-button" onclick="showSection('arsip')"><i class="fas fa-archive"></i> Arsip</div>
            </div>

            <!-- Verifikasi Pendaftaran Section -->
            <div id="verifikasi" class="tab-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-check-circle"></i> Verifikasi Pendaftaran Baru</h3>
                    </div>
                    <div class="card-body">
                        <input type="text" id="searchVerifikasi" class="form-control search-input mb-3" placeholder="Cari nama atau email...">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>NIM</th>
                                    <th>Universitas/Sekolah</th>
                                    <th>Jurusan</th>
                                    <th>No. HP</th>
                                    <th>Alamat</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Tanggal Keluar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="verifikasiTable">
                                <?php if (mysqli_num_rows($pending_registrations) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($pending_registrations)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nama']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['nim']) ?></td>
                                            <td><?= htmlspecialchars($row['universitas']) ?></td>
                                            <td><?= htmlspecialchars($row['jurusan']) ?></td>
                                            <td><?= htmlspecialchars($row['no_hp']) ?></td>
                                            <td><?= htmlspecialchars($row['alamat']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal_masuk'])) ?></td>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal_keluar'])) ?></td>
                                            <td>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="register_id" value="<?= $row['id'] ?>">
                                                    <select name="bidang" class="form-select d-inline-block w-auto mb-2">
                                                        <?php foreach ($bidang_options as $bidang): ?>
                                                            <option value="<?= $bidang ?>"><?= $bidang ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" name="verify_registration" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Verifikasi
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center">Tidak ada pendaftaran pending.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_pending_pages; $i++): ?>
                                <a href="?tab=verifikasi&page_peserta=<?= $i ?>&search_peserta=<?= urlencode($search_peserta) ?>" class="<?= $i === $page_peserta ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Peserta Section -->
            <div id="daftar_peserta" class="tab-section" style="display:none">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> Daftar Peserta Aktif</h3>
                    </div>
                    <div class="card-body">
                        <input type="text" id="searchPeserta" class="form-control search-input mb-3" placeholder="Cari nama, email, atau bidang...">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th>Institusi</th>
                                    <th>Bidang</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Tanggal Keluar</th>
                                    <th>Alamat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pesertaTable">
                                <?php if (mysqli_num_rows($peserta) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($peserta)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nama']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['telepon']) ?></td>
                                            <td><?= htmlspecialchars($row['institusi']) ?></td>
                                            <td><?= htmlspecialchars($row['bidang']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal_masuk'])) ?></td>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal_keluar'])) ?></td>
                                            <td><?= htmlspecialchars($row['alamat']) ?></td>
                                            <td>
                                                <button class="btn btn-primary btn-sm" data-action="view" data-id="<?= $row['id'] ?>">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                                <button class="btn btn-success btn-sm" data-action="edit" data-id="<?= $row['id'] ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin menghapus peserta ini?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Tidak ada peserta aktif.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_peserta_pages; $i++): ?>
                                <a href="?tab=daftar_peserta&page_peserta=<?= $i ?>&search_peserta=<?= urlencode($search_peserta) ?>" class="<?= $i === $page_peserta ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Tambah Peserta Baru</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nama</label>
                                        <input type="text" name="nama" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Telepon</label>
                                        <input type="text" name="telepon" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Institusi</label>
                                        <select name="institusi_id" class="form-control" required onchange="toggleCreateInstitusi(this)">
                                            <option value="">Pilih Institusi</option>
                                            <?php $inst_list = mysqli_query($conn, "SELECT * FROM institusi ORDER BY nama"); ?>
                                            <?php while ($inst = mysqli_fetch_assoc($inst_list)): ?>
                                                <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['nama']) ?></option>
                                            <?php endwhile; ?>
                                            <option value="new">Tambah Institusi Baru</option>
                                        </select>
                                    </div>
                                    <div id="createInstitusiForm" class="hidden">
                                        <div class="form-group">
                                            <label>Nama Institusi</label>
                                            <input type="text" name="institusi_nama" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Alamat Institusi</label>
                                            <input type="text" name="institusi_alamat" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Telepon Institusi</label>
                                            <input type="text" name="institusi_telepon" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Bidang</label>
                                        <select name="bidang" class="form-control" required>
                                            <?php foreach ($bidang_options as $bidang): ?>
                                                <option value="<?= $bidang ?>"><?= $bidang ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Masuk</label>
                                        <input type="date" name="tanggal_masuk" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Tanggal Keluar</label>
                                        <input type="date" name="tanggal_keluar" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Alamat</label>
                                        <textarea name="alamat" class="form-control" rows="3" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Foto (Opsional)</label>
                                        <div class="foto-placeholder" id="fotoPlaceholder">
                                            <i class="fas fa-user-circle fa-3x"></i>
                                        </div>
                                        <img id="fotoPreview" class="foto-preview" src="#" alt="Preview">
                                        <input type="file" name="foto" id="foto" accept="image/*" class="form-control mt-2">
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button type="submit" name="tambah" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Peserta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Daftar Institusi Section -->
            <div id="daftar_institusi" class="tab-section" style="display:none">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-building"></i> Daftar Institusi</h3>
                    </div>
                    <div class="card-body">
                        <input type="text" id="searchInstitusi" class="form-control search-input mb-3" placeholder="Cari nama institusi...">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Alamat</th>
                                    <th>Telepon</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="institusiTable">
                                <?php if (mysqli_num_rows($institusi) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($institusi)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nama']) ?></td>
                                            <td><?= htmlspecialchars($row['alamat']) ?></td>
                                            <td><?= htmlspecialchars($row['telepon']) ?></td>
                                            <td>
                                                <button class="btn btn-success btn-sm" onclick="editInstitusi(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['alamat'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['telepon'], ENT_QUOTES) ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="?hapus_institusi=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin menghapus institusi ini?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada institusi.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_institusi_pages; $i++): ?>
                                <a href="?tab=daftar_institusi&page_institusi=<?= $i ?>&search_institusi=<?= urlencode($search_institusi) ?>" class="<?= $i === $page_institusi ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Tambah Institusi Baru</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>Nama Institusi</label>
                                <input type="text" name="nama" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Alamat</label>
                                <textarea name="alamat" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Telepon</label>
                                <input type="text" name="telepon" class="form-control" required>
                            </div>
                            <div class="text-center">
                                <button type="submit" name="tambah_institusi" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Institusi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Arsip Section -->
            <div id="arsip" class="tab-section" style="display:none">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-archive"></i> Arsip Peserta</h3>
                    </div>
                    <div class="card-body">
                        <input type="text" id="searchArsip" class="form-control search-input mb-3" placeholder="Cari nama, keterangan, atau bidang...">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Peserta</th>
                                    <th>Bidang</th>
                                    <th>Keterangan</th>
                                    <th>Tanggal Arsip</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="arsipTable">
                                <?php if (mysqli_num_rows($arsip) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($arsip)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['peserta_nama']) ?></td>
                                            <td><?= htmlspecialchars($row['bidang']) ?></td>
                                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal_arsip'])) ?></td>
                                            <td>
                                                <button class="btn btn-primary btn-sm" data-action="view" data-id="<?= $row['peserta_id'] ?>">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada data arsip.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_arsip_pages; $i++): ?>
                                <a href="?tab=arsip&page_arsip=<?= $i ?>&search_arsip=<?= urlencode($search_arsip) ?>" class="<?= $i === $page_arsip ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('detailModal')">&times;</span>
            <h3 class="text-center mb-3"><i class="fas fa-user-circle"></i> Detail Peserta</h3>
            <div id="detailContent"></div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h3 class="text-center mb-3"><i class="fas fa-edit"></i> Edit Peserta</h3>
            <form method="post" id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_peserta_id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" id="edit_nama" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="edit_email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Telepon</label>
                            <input type="text" id="edit_telepon" name="telepon" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Institusi</label>
                            <select id="edit_institusi_id" name="institusi_id" class="form-control" required>
                                <option value="">Pilih Institusi</option>
                                <?php $inst_list = mysqli_query($conn, "SELECT * FROM institusi ORDER BY nama"); ?>
                                <?php while ($inst = mysqli_fetch_assoc($inst_list)): ?>
                                    <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['nama']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bidang</label>
                            <select id="edit_bidang" name="bidang" class="form-control" required>
                                <?php foreach ($bidang_options as $bidang): ?>
                                    <option value="<?= $bidang ?>"><?= $bidang ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tanggal Masuk</label>
                            <input type="date" id="edit_tanggal_masuk" name="tanggal_masuk" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Keluar</label>
                            <input type="date" id="edit_tanggal_keluar" name="tanggal_keluar" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea id="edit_alamat" name="alamat" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Foto Baru (Opsional)</label>
                            <div class="foto-placeholder" id="editFotoPlaceholder">
                                <i class="fas fa-user-circle fa-3x"></i>
                            </div>
                            <img id="editFotoPreview" class="foto-preview" src="#" alt="Preview">
                            <input type="file" name="foto" id="edit_foto" accept="image/*" class="form-control mt-2">
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button type="submit" name="edit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Peserta
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Institusi Modal -->
    <div id="editInstitusiModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editInstitusiModal')">&times;</span>
            <h3 class="text-center mb-3"><i class="fas fa-building"></i> Edit Institusi</h3>
            <form method="post">
                <input type="hidden" name="id" id="edit_institusi_id">
                <div class="form-group">
                    <label>Nama Institusi</label>
                    <input type="text" id="edit_nama_institusi" name="nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea id="edit_alamat_institusi" name="alamat" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Telepon</label>
                    <input type="text" id="edit_telepon_institusi" name="telepon" class="form-control" required>
                </div>
                <div class="text-center">
                    <button type="submit" name="edit_institusi" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Institusi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('hidden');
            mainContent.style.marginLeft = window.innerWidth <= 768 ? '0' : (sidebar.classList.contains('hidden') ? '0' : '280px');
        }

        function showSection(sectionId) {
            document.querySelectorAll('.tab-section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId).style.display = 'block';

            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            document.querySelector(`.tab-button[onclick="showSection('${sectionId}')"]`).classList.add('active');
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