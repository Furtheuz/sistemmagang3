<?php
require __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

include "config/db.php";

// Validate and sanitize input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: Invalid or missing ID parameter");
}

$id = (int) $_GET['id'];

// Check database connection
if (!$conn) {
    die("Error: Database connection failed - " . mysqli_connect_error());
}

// Execute query with error handling
$query = "
    SELECT p.*, u.nama, u.email
    FROM peserta p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = $id
";

$result = mysqli_query($conn, $query);

// Check if query executed successfully
if (!$result) {
    die("Error: Query failed - " . mysqli_error($conn));
}

// Check if data exists
$data = mysqli_fetch_assoc($result);
if (!$data) {
    die("Error: No data found for ID: $id");
}

// Konversi foto ke base64 jika ada
$foto_base64 = '';
if (!empty($data['foto']) && file_exists($data['foto'])) {
    $foto_base64 = 'data:image/' . pathinfo($data['foto'], PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($data['foto']));
} else {
    // Foto placeholder jika tidak ada foto
    $foto_base64 = 'data:image/svg+xml;base64,' . base64_encode('
        <svg width="120" height="120" xmlns="http://www.w3.org/2000/svg">
            <rect width="120" height="120" fill="#f0f0f0" stroke="#ccc" stroke-width="2"/>
            <text x="60" y="65" text-anchor="middle" font-family="Arial" font-size="14" fill="#999">No Photo</text>
        </svg>
    ');
}

$html = '
<style>
    body { 
        font-family: "Arial Black", "Arial", sans-serif; 
        margin: 0; 
        padding: 0; 
        background: #f5f5f5;
    }
    
    .idcard {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #003366 0%, #0066cc 50%, #0099ff 100%);
        border: 3px solid #ffcc00;
        border-radius: 15px;
        padding: 20px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        position: relative;
        overflow: hidden;
    }
    
    .idcard::before {
        content: "";
        position: absolute;
        top: -20px;
        left: -20px;
        right: -20px;
        height: 60px;
        background: linear-gradient(90deg, #ffcc00 0%, #ffdd33 50%, #ffcc00 100%);
        z-index: 1;
    }
    
    .idcard::after {
        content: "";
        position: absolute;
        bottom: -20px;
        left: -20px;
        right: -20px;
        height: 40px;
        background: linear-gradient(90deg, #ffcc00 0%, #ffaa00 50%, #ffcc00 100%);
        z-index: 1;
    }
    
    .header {
        text-align: center;
        color: #ffcc00;
        z-index: 2;
        width: 100%;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        margin-top: 25px;
    }
    
    .company-name {
        font-size: 13px;
        font-weight: 900;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #ffffff;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
    }
    
    .card-title {
        font-size: 16px;
        font-weight: 900;
        margin: 8px 0;
        color: #ffcc00;
        text-shadow: 3px 3px 6px rgba(0,0,0,0.8);
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .photo-container {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        border: 5px solid #ffcc00;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        z-index: 2;
        background: white;
        position: relative;
    }
    
    .photo-container::before {
        content: "";
        position: absolute;
        top: -3px;
        left: -3px;
        right: -3px;
        bottom: -3px;
        border-radius: 50%;
        background: linear-gradient(45deg, #ffcc00, #ffdd33, #ffcc00);
        z-index: -1;
    }
    
    .photo {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .info-section {
        background: linear-gradient(135deg, rgba(255,204,0,0.95) 0%, rgba(255,221,51,0.95) 100%);
        border: 2px solid #0066cc;
        border-radius: 12px;
        padding: 12px;
        width: 100%;
        box-sizing: border-box;
        z-index: 2;
        text-align: left;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .info-row {
        margin-bottom: 6px;
        display: flex;
        align-items: center;
    }
    
    .info-label {
        font-weight: 900;
        color: #003366;
        font-size: 10px;
        width: 75px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        color: #000066;
        font-size: 10px;
        font-weight: bold;
        flex: 1;
    }
    
    .footer {
        text-align: center;
        color: #ffcc00;
        font-size: 8px;
        z-index: 2;
        width: 100%;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        margin-bottom: 15px;
    }
    
    .validity {
        background: linear-gradient(135deg, rgba(255,204,0,0.3) 0%, rgba(255,221,51,0.3) 100%);
        border: 1px solid #ffcc00;
        border-radius: 8px;
        padding: 4px 8px;
        margin-top: 5px;
        font-size: 7px;
        font-weight: bold;
        color: #ffffff;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
    }
    
    .hero-emblem {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 25px;
        height: 25px;
        background: #ffcc00;
        border-radius: 50%;
        border: 2px solid #ffffff;
        z-index: 3;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 12px;
        color: #003366;
        box-shadow: 0 3px 8px rgba(0,0,0,0.4);
    }
</style>

<div class="idcard">
    <div class="hero-emblem">I</div>
    
    <div class="header">
        <p class="company-name">DPUPR</p>
        <h2 class="card-title">ID CARD</h2>
    </div>
    
    <div class="photo-container">
        <img src="' . $foto_base64 . '" alt="Foto Peserta" class="photo">
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value">' . htmlspecialchars($data['nama']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">' . htmlspecialchars($data['email']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Asal Institusi:</span>
            <span class="info-value">' . htmlspecialchars($data['institusi_id']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value">' . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . '</span>
        </div>
    </div>
    
    <div class="footer">
        <div class="validity">
            CLEARANCE EXPIRES: ' . date('d M Y', strtotime('+6 months')) . '
        </div>
        <p>ISSUED: ' . date('d M Y') . ' | AUTHORIZED PERSONNEL ONLY</p>
    </div>
</div>';

// Close database connection
mysqli_close($conn);

$dompdf = new Dompdf();
$dompdf->loadHtml($html);

// Set ukuran portrait untuk ID Card (54mm x 86mm dalam points)
$dompdf->setPaper([0, 0, 153, 244]); // Portrait orientation

$dompdf->render();
$dompdf->stream("idcard_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $data['nama']) . ".pdf", ["Attachment" => false]);
?>