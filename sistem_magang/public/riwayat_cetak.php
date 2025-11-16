<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

/* 🎨  Tema biru untuk semua role */
$colors = [
    'primary' => '#2563eb',     // Biru utama
    'secondary' => '#eff6ff',   // Biru muda untuk background
    'accent' => '#1d4ed8',      // Biru gelap untuk aksen
    'light' => '#dbeafe'        // Biru sangat muda
];

/* 🔍  Ambil riwayat cetak + nama peserta + nama user */
$sql = "
    SELECT r.*, 
           p.nama AS peserta_nama,
           u.nama AS user_nama
    FROM riwayat_cetak_idcard r
    LEFT JOIN peserta p ON p.id = r.peserta_id
    LEFT JOIN users   u ON u.id = r.user_id
    ORDER BY r.created_at DESC
";
$log = mysqli_query($conn, $sql) or die('SQL error: '.mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Riwayat Cetak ID Card</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: <?= $colors['secondary'] ?>;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .main-header {
      background: linear-gradient(135deg, <?= $colors['primary'] ?> 0%, <?= $colors['accent'] ?> 100%);
      color: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 25px;
      box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
    }
    
    .main-header h1 {
      margin: 0;
      font-size: 1.8rem;
      font-weight: 600;
    }
    
    .btn-back {
      background: rgba(255, 255, 255, 0);
      color: white;
      border: 2px solid rgba(255, 255, 255, 0);
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      backdrop-filter: blur(0px);
    }
    
    .btn-back:hover {
      background: rgba(255, 255, 255, 0.3);
      color: white;
      border-color: rgba(255, 255, 255, 0.5);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
    }
    
    .btn-back i {
      margin-right: 6px;
      font-size: 0.75em;
    }
    
    .main-header i {
      margin-right: 10px;
      font-size: 1.5rem;
    }
    
    .table-container {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .table thead th {
      background: <?= $colors['primary'] ?> !important;
      color: white !important;
      border: none;
      padding: 15px 12px;
      font-weight: 600;
      text-align: center;
    }
    
    .table tbody tr:hover {
      background-color: <?= $colors['light'] ?>;
      transition: background-color 0.3s ease;
    }
    
    .table tbody td {
      padding: 12px;
      vertical-align: middle;
      border-color: <?= $colors['light'] ?>;
    }
    
    .table tbody tr:nth-child(even) {
      background-color: #f8fafc;
    }
    
    .badge-number {
      background: <?= $colors['primary'] ?>;
      color: white;
      padding: 6px 10px;
      border-radius: 20px;
      font-weight: 500;
      font-size: 0.9rem;
    }
    
    .peserta-name {
      font-weight: 600;
      color: <?= $colors['accent'] ?>;
    }
    
    .user-name {
      color: #64748b;
      font-style: italic;
    }
    
    .datetime {
      color: #475569;
      font-size: 0.9rem;
    }
    
    .jumlah-badge {
      background: <?= $colors['accent'] ?>;
      color: white;
      padding: 4px 12px;
      border-radius: 15px;
      font-weight: 500;
      font-size: 0.85rem;
    }
    
    .no-data {
      text-align: center;
      color: #64748b;
      font-style: italic;
      padding: 40px;
    }
  </style>
</head>
<body class="p-4">
  <div class="main-header">
    <div class="d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-history"></i> Riwayat Cetak ID Card</h1>
      <a href="dashboard.php" class="btn btn-back">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </div>

  <div class="table-container">
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr>
            <th style="width: 60px;">#</th>
            <th>Nama Peserta</th>
            <th>Dicetak Oleh</th>
            <th style="width: 180px;">Waktu</th>
            <th style="width: 100px;">Jumlah</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $no = 1; 
          $hasData = false;
          while ($r = mysqli_fetch_assoc($log)): 
            $hasData = true;
          ?>
          <tr>
            <td class="text-center">
              <span class="badge-number"><?= $no++ ?></span>
            </td>
            <td>
              <span class="peserta-name"><?= htmlspecialchars($r['peserta_nama']) ?></span>
            </td>
            <td>
              <span class="user-name"><?= htmlspecialchars($r['user_nama']) ?></span>
            </td>
            <td>
              <span class="datetime"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
            </td>
            <td class="text-center">
              <span class="jumlah-badge"><?= $r['jumlah'] ?></span>
            </td>
          </tr>
          <?php endwhile; ?>
          
          <?php if (!$hasData): ?>
          <tr>
            <td colspan="5" class="no-data">
              <i class="fas fa-info-circle"></i> Belum ada riwayat cetak ID Card
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>