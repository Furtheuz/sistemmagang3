<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

// SETTINGS
$ids = isset($_POST['ids']) ? array_filter($_POST['ids'], 'intval') : [];
if (isset($_GET['ids'])) {
    $ids = array_filter(explode(',', $_GET['ids']), 'intval');
}

// ROLE-BASED COLOR PALETTE
$role = $_SESSION['user']['role'] ?? 'user';
$roleColors = [
    'admin'      => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'],
    'pembimbing' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'],
    'user'       => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8']
];
$colors = $roleColors[$role] ?? $roleColors['user'];

// Query peserta yang dipilih
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT p.*, i.nama AS instansi FROM peserta p LEFT JOIN institusi i ON i.id = p.institusi_id WHERE p.id IN ($in)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, str_repeat('i', count($ids)), ...$ids);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    if (!$sql) {
        die('Query failed: ' . mysqli_error($conn));
    }
} else {
    $sql = false;
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
            width: 22mm;
            height: 22mm;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            background: #f5f5f5;
            position: absolute;
            top: 41.2px;
            left: 32.8px;
            z-index: 10;
        }

        .idcard .participant-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
            text-align: center;
            margin: 140px 8px 20px 8px;
            line-height: 2.4;
            text-transform: uppercase;
        }

        .idcard .info-section {
            padding: 0 12px;
            text-align: left;
            width: 100%;
            flex-grow: 1;
        }

        .idcard .info-row {
            margin-bottom: 8px;
            font-size: 11px;
            line-height: 1.4;
        }

        .idcard .info-label {
            font-weight: 600;
            color: #1e40af;
            display: block;
            margin-bottom: 2px;
        }

        .idcard .info-value {
            color: #000;
            font-weight: 500;
            display: block;
            margin-left: 8px;
        }

        .idcard .footer {
            background: var(--secondary-color);
            width: 100%;
            padding: 4px;
            font-size: 8px;
            text-align: center;
            color: #666;
            border-top: 1px solid #eee;
            margin-top: auto;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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

        .table {
            font-size: 14px;
        }

        .table thead th {
            background: var(--secondary-color);
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background: rgba(0, 0, 0, 0.02);
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
                -webkit-print-color-adjust: exact;
                background: #fff url('images/bck.jpeg') no-repeat center center !important;
                background-size: cover !important;
            }
            .no-print {
                display: none;
            }
            .d-flex {
                display: block !important;
            }
        }

        @media (max-width: 768px) {
            .idcard {
                margin: 5mm auto;
            }
            .table-responsive {
                max-height: none;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="h4 fw-bold mb-4"><i class="fa fa-id-card me-2"></i> Cetak ID Card</h1>

    <?php if (!$ids): ?>
        <form method="post" class="card shadow-sm p-4">
            <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>Nama</th>
                            <th>Bidang</th>
                            <th>Asal Instansi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = mysqli_query($conn, "SELECT p.id, p.nama, p.bidang, i.nama AS instansi FROM peserta p LEFT JOIN institusi i ON i.id = p.institusi_id WHERE p.status = 'aktif' ORDER BY p.nama");
                        if (mysqli_num_rows($q) == 0) {
                            echo '<tr><td colspan="4" class="text-muted text-center py-3">Tidak ada peserta aktif.</td></tr>';
                        } else {
                            while ($p = mysqli_fetch_assoc($q)):
                        ?>
                            <tr>
                                <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?=$p['id']?>"></td>
                                <td><?=htmlspecialchars($p['nama'])?></td>
                                <td><?=htmlspecialchars($p['bidang'] ?? '-')?></td>
                                <td><?=htmlspecialchars($p['instansi'] ?? '-')?></td>
                            </tr>
                        <?php endwhile; } ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><i class="fa fa-print me-2"></i> Cetak Terpilih</button>
        </form>
        <script>
            document.getElementById('checkAll').addEventListener('change', e => {
                document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => cb.checked = e.target.checked);
            });
        </script>
    <?php else: ?>
        <div class="no-print mb-4">
            <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print me-2"></i> Cetak ID Cards</button>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <?php while ($row = mysqli_fetch_assoc($sql)): ?>
                <div class="idcard">
                    <div class="header"> </div>
                    <?php
                    $photo = $row['foto'] ? basename($row['foto']) : 'default.png';
                    $photoPath = "Uploads/peserta/$photo";
                    if (!file_exists($photoPath)) {
                        $photoPath = 'Uploads/peserta/default.png';
                    }
                    ?>
                    <img src="<?=$photoPath?>" class="photo" alt="Foto <?=htmlspecialchars($row['nama'])?>">
                    
                    <div class="participant-name">
                        <?=htmlspecialchars(strtoupper($row['nama']))?>
                    </div>
                    
                    <div class="info-section">
                        <div class="info-row">
                            <span class="info-label">Bidang</span>
                            <span class="info-value">: <?=htmlspecialchars($row['bidang'] ?? '-')?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Asal Institusi</span>
                            <span class="info-value">: <?=htmlspecialchars($row['instansi'] ?? '-')?></span>
                        </div>
                    </div>
                    
                    <div class="footer">Dikeluarkan oleh: SPAM DPUPR Banten</div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>