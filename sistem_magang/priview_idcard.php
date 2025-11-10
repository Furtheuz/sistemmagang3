<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

// Pastikan hanya role 'user' yang bisa mengakses
$role = $_SESSION['user']['role'] ?? 'user';
if ($role !== 'user') {
    header("Location: dashboard.php");
    exit;
}

// ROLE-BASED COLOR PALETTE
$roleColors = [
    'admin'      => ['primary' => '#dc2626', 'secondary' => '#fef2f2', 'accent' => '#b91c1c'],
    'pembimbing' => ['primary' => '#059669', 'secondary' => '#f0fdf4', 'accent' => '#047857'],
    'user'       => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8']
];
$colors = $roleColors[$role];

// Ambil data peserta berdasarkan user_id dari sesi
$user_id = $_SESSION['user']['id'];
$sql = "SELECT p.*, i.nama AS instansi 
        FROM peserta p 
        LEFT JOIN institusi i ON i.id = p.institusi_id 
        WHERE p.user_id = ? AND p.status = 'aktif'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
}

$peserta = mysqli_fetch_assoc($result);
if (!$peserta) {
    $error_message = "Data peserta tidak ditemukan atau status tidak aktif.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak ID Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root {
            --primary-color: <?=$colors['primary']?>;
            --secondary-color: <?=$colors['secondary']?>;
            --accent-color: <?=$colors['accent']?>;
        }

        body {
            background: var(--secondary-color);
            color: #111;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .idcard {
            width: 54mm;
            height: 86mm;
            position: relative;
            border: 1px solid var(--primary-color);
            border-radius: 8px;
            overflow: hidden;
            margin: 10mm auto;
            page-break-inside: avoid;
            background: #fff url('images/bck.jpeg') no-repeat center center;
            background-size: cover;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .idcard .header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: #fff;
            text-align: center;
            padding: 10px;
            width: 100%;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .idcard .lanyard-hole {
            position: absolute;
            top: 6px;
            left: 50%;
            transform: translateX(-50%);
            width: 6mm;
            height: 6mm;
            background: #fff;
            border: 1px solid var(--primary-color);
            border-radius: 50%;
            z-index: 10;
        }

        .idcard img.photo {
            width: 30mm;
            height: 30mm;
            object-fit: cover;
            border-radius: 50%;
            margin: 12px 0;
            border: 2px solid var(--primary-color);
            background: #f5f5f5;
        }

        .idcard .info {
            padding: 0 8px 8px;
            font-size: 10px;
            line-height: 1.3;
            text-align: center;
            color: #333;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .idcard .info b {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--primary-color);
        }

        .idcard .status-text {
            font-size: 10px;
            font-weight: 600;
            color: var(--accent-color);
            text-transform: uppercase;
            margin: 4px 0;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 8px;
            border-radius: 4px;
        }

        .idcard .footer {
            background: var(--secondary-color);
            width: 100%;
            padding: 4px;
            font-size: 8px;
            text-align: center;
            color: #666;
            border-top: 1px solid #eee;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .btn-primary:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }
            .container {
                max-width: none;
            }
            .idcard {
                margin: 5mm auto;
                box-shadow: none;
                border: 1px solid #ccc;
            }
            .no-print {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .idcard {
                margin: 5mm auto;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="h4 fw-bold mb-4"><i class="fa fa-id-card me-2"></i> Cetak ID Card</h1>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php else: ?>
        <div class="no-print mb-4">
            <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print me-2"></i> Cetak ID Card</button>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <div class="idcard">
                <div class="header"> </div>
                <?php
                $photo = $peserta['foto'] ? basename($peserta['foto']) : 'default.png';
                $photoPath = "uploads/$photo";
                if (!file_exists($photoPath)) {
                    $photoPath = 'Uploads/peserta/default.png';
                }
                ?>
                <img src="<?= $photoPath ?>" class="photo" alt="Foto <?= htmlspecialchars($peserta['nama']) ?>">
                <div class="info">
                    <div class="status-text">
                        <?= htmlspecialchars($peserta['status'] == 'mahasiswa' ? 'Peserta Magang' : 'Peserta PKL') ?>
                    </div>
                    <b><?= htmlspecialchars($peserta['nama']) ?></b>
                    <span>Bidang: <?= htmlspecialchars($peserta['bidang'] ?? '-') ?></span>
                    <span>Instansi: <?= htmlspecialchars($peserta['instansi'] ?? '-') ?></span>
                </div>
                <div class="footer">Dikeluarkan oleh: SPAM DPUPR Banten</div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>