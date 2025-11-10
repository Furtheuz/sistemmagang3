<?php
// File: jadwal_backend.php
// Backend logic untuk sistem jadwal magang

// Pastikan session sudah dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
// require_once 'config/database.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Inisialisasi variabel
$pesan = '';
$jadwal_by_minggu = [];

// Fungsi untuk mendapatkan peserta berdasarkan role
function getPesertaByRole($conn, $role, $user_id) {
    if ($role == 'admin') {
        // Admin bisa lihat semua peserta
        $query = "SELECT u.id, u.nama, u.email, u.institusi, u.status 
                  FROM users u 
                  WHERE u.role = 'user' 
                  ORDER BY u.nama";
    } elseif ($role == 'pembimbing') {
        // Pembimbing hanya bisa lihat peserta yang dibimbingnya
        $query = "SELECT u.id, u.nama, u.email, u.institusi, u.status 
                  FROM users u 
                  WHERE u.role = 'user' AND u.pembimbing_id = ? AND u.status = 'accepted'
                  ORDER BY u.nama";
    } else {
        return false;
    }
    
    if ($role == 'pembimbing') {
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    } else {
        return mysqli_query($conn, $query);
    }
}

// Fungsi untuk mendapatkan peserta pending (khusus pembimbing)
function getPesertaPending($conn, $pembimbing_id) {
    $query = "SELECT u.id, u.nama, u.email, u.institusi, u.status 
              FROM users u 
              WHERE u.role = 'user' AND u.pembimbing_id = ? AND u.status = 'pending'
              ORDER BY u.nama";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $pembimbing_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// Fungsi untuk mendapatkan peserta yang sudah diterima (khusus pembimbing)
function getPesertaAccepted($conn, $pembimbing_id) {
    $query = "SELECT u.id, u.nama, u.email, u.institusi, u.status 
              FROM users u 
              WHERE u.role = 'user' AND u.pembimbing_id = ? AND u.status = 'accepted'
              ORDER BY u.nama";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $pembimbing_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// Fungsi untuk mendapatkan daftar pembimbing (khusus admin)
function getPembimbing($conn) {
    $query = "SELECT id, nama FROM users WHERE role = 'pembimbing' ORDER BY nama";
    return mysqli_query($conn, $query);
}

// Fungsi untuk mendapatkan jadwal berdasarkan role
function getJadwalByRole($conn, $role, $user_id) {
    if ($role == 'admin') {
        // Admin bisa lihat semua jadwal
        $query = "SELECT j.id, j.tanggal, j.minggu, j.tugas, j.peserta_id, j.pembimbing_id,
                         u1.nama as peserta, u1.institusi,
                         u2.nama as pembimbing
                  FROM jadwal j
                  JOIN users u1 ON j.peserta_id = u1.id
                  JOIN users u2 ON j.pembimbing_id = u2.id
                  ORDER BY j.minggu, j.tanggal";
        return mysqli_query($conn, $query);
    } elseif ($role == 'pembimbing') {
        // Pembimbing hanya bisa lihat jadwal peserta yang dibimbingnya
        $query = "SELECT j.id, j.tanggal, j.minggu, j.tugas, j.peserta_id, j.pembimbing_id,
                         u1.nama as peserta, u1.institusi,
                         u2.nama as pembimbing
                  FROM jadwal j
                  JOIN users u1 ON j.peserta_id = u1.id
                  JOIN users u2 ON j.pembimbing_id = u2.id
                  WHERE j.pembimbing_id = ?
                  ORDER BY j.minggu, j.tanggal";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    } else {
        // User hanya bisa lihat jadwal sendiri
        $query = "SELECT j.id, j.tanggal, j.minggu, j.tugas, j.peserta_id, j.pembimbing_id,
                         u1.nama as peserta, u1.institusi,
                         u2.nama as pembimbing
                  FROM jadwal j
                  JOIN users u1 ON j.peserta_id = u1.id
                  JOIN users u2 ON j.pembimbing_id = u2.id
                  WHERE j.peserta_id = ?
                  ORDER BY j.minggu, j.tanggal";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }
}

// Proses form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Proses tambah jadwal (admin)
    if (isset($_POST['tambah_jadwal']) && $role == 'admin') {
        $peserta_id = $_POST['peserta_id'];
        $pembimbing_id = $_POST['pembimbing_id'];
        $tanggal = $_POST['tanggal'];
        $minggu = $_POST['minggu'];
        $tugas = $_POST['tugas'];
        
        // Validasi input
        if (empty($peserta_id) || empty($pembimbing_id) || empty($tanggal) || empty($minggu) || empty($tugas)) {
            $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Semua field harus diisi!</div>';
        } else {
            // Cek apakah jadwal sudah ada untuk minggu dan peserta yang sama
            $check_query = "SELECT id FROM jadwal WHERE peserta_id = ? AND minggu = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "ii", $peserta_id, $minggu);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $pesan = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Jadwal untuk minggu ini sudah ada!</div>';
            } else {
                $query = "INSERT INTO jadwal (peserta_id, pembimbing_id, tanggal, minggu, tugas) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iisis", $peserta_id, $pembimbing_id, $tanggal, $minggu, $tugas);
                
                if (mysqli_stmt_execute($stmt)) {
                    $pesan = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal berhasil ditambahkan!</div>';
                } else {
                    $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal menambahkan jadwal!</div>';
                }
            }
        }
    }
    
    // Proses tambah jadwal (pembimbing)
    if (isset($_POST['tambah_jadwal_pembimbing']) && $role == 'pembimbing') {
        $peserta_id = $_POST['peserta_id'];
        $tanggal = $_POST['tanggal'];
        $minggu = $_POST['minggu'];
        $tugas = $_POST['tugas'];
        
        // Validasi input
        if (empty($peserta_id) || empty($tanggal) || empty($minggu) || empty($tugas)) {
            $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Semua field harus diisi!</div>';
        } else {
            // Cek apakah peserta benar-benar dibimbing oleh pembimbing ini
            $check_peserta = "SELECT id FROM users WHERE id = ? AND pembimbing_id = ? AND status = 'accepted'";
            $check_stmt = mysqli_prepare($conn, $check_peserta);
            mysqli_stmt_bind_param($check_stmt, "ii", $peserta_id, $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) == 0) {
                $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Peserta tidak ditemukan atau bukan bimbingan Anda!</div>';
            } else {
                // Cek apakah jadwal sudah ada untuk minggu dan peserta yang sama
                $check_jadwal = "SELECT id FROM jadwal WHERE peserta_id = ? AND minggu = ?";
                $check_stmt2 = mysqli_prepare($conn, $check_jadwal);
                mysqli_stmt_bind_param($check_stmt2, "ii", $peserta_id, $minggu);
                mysqli_stmt_execute($check_stmt2);
                $check_result2 = mysqli_stmt_get_result($check_stmt2);
                
                if (mysqli_num_rows($check_result2) > 0) {
                    $pesan = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Jadwal untuk minggu ini sudah ada!</div>';
                } else {
                    $query = "INSERT INTO jadwal (peserta_id, pembimbing_id, tanggal, minggu, tugas) VALUES (?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "iisis", $peserta_id, $user_id, $tanggal, $minggu, $tugas);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $pesan = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal berhasil ditambahkan!</div>';
                    } else {
                        $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal menambahkan jadwal!</div>';
                    }
                }
            }
        }
    }
    
    // Proses update jadwal
    if (isset($_POST['update_jadwal']) && ($role == 'admin' || $role == 'pembimbing')) {
        $jadwal_id = $_POST['jadwal_id'];
        $tanggal = $_POST['tanggal'];
        $minggu = $_POST['minggu'];
        $tugas = $_POST['tugas'];
        
        // Validasi input
        if (empty($jadwal_id) || empty($tanggal) || empty($minggu) || empty($tugas)) {
            $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Semua field harus diisi!</div>';
        } else {
            // Query berbeda untuk admin dan pembimbing
            if ($role == 'admin') {
                $query = "UPDATE jadwal SET tanggal = ?, minggu = ?, tugas = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sisi", $tanggal, $minggu, $tugas, $jadwal_id);
            } else {
                // Pembimbing hanya bisa update jadwal peserta yang dibimbingnya
                $query = "UPDATE jadwal SET tanggal = ?, minggu = ?, tugas = ? WHERE id = ? AND pembimbing_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sisii", $tanggal, $minggu, $tugas, $jadwal_id, $user_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $pesan = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal berhasil diperbarui!</div>';
            } else {
                $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal memperbarui jadwal!</div>';
            }
        }
    }
    
    // Proses hapus jadwal
    if (isset($_POST['hapus_jadwal']) && ($role == 'admin' || $role == 'pembimbing')) {
        $jadwal_id = $_POST['jadwal_id'];
        
        if (empty($jadwal_id)) {
            $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ID jadwal tidak valid!</div>';
        } else {
            // Query berbeda untuk admin dan pembimbing
            if ($role == 'admin') {
                $query = "DELETE FROM jadwal WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $jadwal_id);
            } else {
                // Pembimbing hanya bisa hapus jadwal peserta yang dibimbingnya
                $query = "DELETE FROM jadwal WHERE id = ? AND pembimbing_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $jadwal_id, $user_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $pesan = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal berhasil dihapus!</div>';
            } else {
                $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal menghapus jadwal!</div>';
            }
        }
    }
    
    // Proses accept peserta (khusus pembimbing)
    if (isset($_POST['accept_peserta']) && $role == 'pembimbing') {
        $peserta_id = $_POST['peserta_id'];
        
        if (empty($peserta_id)) {
            $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ID peserta tidak valid!</div>';
        } else {
            // Update status peserta menjadi accepted
            $query = "UPDATE users SET status = 'accepted' WHERE id = ? AND pembimbing_id = ? AND status = 'pending'";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $peserta_id, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $pesan = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Peserta berhasil diterima!</div>';
                } else {
                    $pesan = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Peserta tidak ditemukan atau sudah diproses!</div>';
                }
            } else {
                $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal menerima peserta!</div>';
            }
        }
    }
    
    // Proses reject peserta (khusus pembimbing)
    if (isset($_POST['reject_peserta']) && $role == 'pembimbing') {
        $peserta_id = $_POST['peserta_id'];
        
        if (empty($peserta_id)) {
            $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ID peserta tidak valid!</div>';
        } else {
            // Update status peserta menjadi rejected dan reset pembimbing_id
            $query = "UPDATE users SET status = 'rejected', pembimbing_id = NULL WHERE id = ? AND pembimbing_id = ? AND status = 'pending'";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $peserta_id, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $pesan = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Peserta berhasil ditolak!</div>';
                } else {
                    $pesan = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Peserta tidak ditemukan atau sudah diproses!</div>';
                }
            } else {
                $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal menolak peserta!</div>';
            }
        }
    }
}

// Ambil data berdasarkan role
if ($role == 'admin') {
    $peserta = getPesertaByRole($conn, $role, $user_id);
    $pembimbing = getPembimbing($conn);
} elseif ($role == 'pembimbing') {
    $peserta = getPesertaByRole($conn, $role, $user_id);
    $peserta_pending = getPesertaPending($conn, $user_id);
    $peserta_accepted = getPesertaAccepted($conn, $user_id);
}

$jadwal_result = getJadwalByRole($conn, $role, $user_id);

// Organisasi jadwal berdasarkan minggu
while ($row = mysqli_fetch_assoc($jadwal_result)) {
    $jadwal_by_minggu[$row['minggu']][] = $row;
}

// Fungsi untuk mendapatkan detail jadwal berdasarkan ID
function getJadwalById($conn, $jadwal_id, $role, $user_id) {
    if ($role == 'admin') {
        $query = "SELECT j.*, u1.nama as peserta, u2.nama as pembimbing 
                  FROM jadwal j
                  JOIN users u1 ON j.peserta_id = u1.id
                  JOIN users u2 ON j.pembimbing_id = u2.id
                  WHERE j.id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $jadwal_id);
    } elseif ($role == 'pembimbing') {
        $query = "SELECT j.*, u1.nama as peserta, u2.nama as pembimbing 
                  FROM jadwal j
                  JOIN users u1 ON j.peserta_id = u1.id
                  JOIN users u2 ON j.pembimbing_id = u2.id
                  WHERE j.id = ? AND j.pembimbing_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $jadwal_id, $user_id);
    } else {
        $query = "SELECT j.*, u1.nama as peserta, u2.nama as pembimbing 
                  FROM jadwal j
                  JOIN users u1 ON j.peserta_id = u1.id
                  JOIN users u2 ON j.pembimbing_id = u2.id
                  WHERE j.id = ? AND j.peserta_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $jadwal_id, $user_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Fungsi untuk mendapatkan statistik jadwal (khusus admin dan pembimbing)
function getStatistikJadwal($conn, $role, $user_id) {
    $stats = [];
    
    if ($role == 'admin') {
        // Total jadwal
        $query = "SELECT COUNT(*) as total FROM jadwal";
        $result = mysqli_query($conn, $query);
        $stats['total_jadwal'] = mysqli_fetch_assoc($result)['total'];
        
        // Jadwal per minggu
        $query = "SELECT minggu, COUNT(*) as jumlah FROM jadwal GROUP BY minggu ORDER BY minggu";
        $result = mysqli_query($conn, $query);
        $stats['per_minggu'] = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $stats['per_minggu'][] = $row;
        }
        
        // Total peserta aktif
        $query = "SELECT COUNT(*) as total FROM users WHERE role = 'user' AND status = 'accepted'";
        $result = mysqli_query($conn, $query);
        $stats['total_peserta'] = mysqli_fetch_assoc($result)['total'];
        
    } elseif ($role == 'pembimbing') {
        // Total jadwal yang dibuat
        $query = "SELECT COUNT(*) as total FROM jadwal WHERE pembimbing_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats['total_jadwal'] = mysqli_fetch_assoc($result)['total'];
        
        // Peserta yang dibimbing
        $query = "SELECT COUNT(*) as total FROM users WHERE pembimbing_id = ? AND status = 'accepted'";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats['total_peserta'] = mysqli_fetch_assoc($result)['total'];
        
        // Peserta pending
        $query = "SELECT COUNT(*) as total FROM users WHERE pembimbing_id = ? AND status = 'pending'";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats['peserta_pending'] = mysqli_fetch_assoc($result)['total'];
    }
    
    return $stats;
}

// Ambil statistik jika diperlukan
if ($role == 'admin' || $role == 'pembimbing') {
    $statistik = getStatistikJadwal($conn, $role, $user_id);
}

// Fungsi untuk format tanggal Indonesia
function formatTanggalIndonesia($tanggal) {
    $bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    
    $pecah = explode('-', $tanggal);
    return $pecah[2] . ' ' . $bulan[$pecah[1]] . ' ' . $pecah[0];
}

// Fungsi untuk validasi tanggal
function validateTanggal($tanggal) {
    $date = DateTime::createFromFormat('Y-m-d', $tanggal);
    return $date && $date->format('Y-m-d') === $tanggal;
}

// Fungsi untuk mendapatkan range minggu
function getMingguRange() {
    return range(1, 12); // Asumsi magang 12 minggu
}

// Close database connection jika ada
// mysqli_close($conn);
?>