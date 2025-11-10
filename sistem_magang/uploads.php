<?php
include "config/db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['file'])) {
    $folder = "uploads/documents/"; // Pastikan folder ini ada
    $filename = basename($_FILES['file']['name']);
    $target = $folder . time() . "_" . $filename;

    // Validasi ekstensi
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo "<div style='color:red'>File tidak diizinkan. Hanya PDF, DOC, JPG, PNG.</div>";
        exit;
    }

    // Validasi ukuran (misal max 2MB)
    if ($_FILES['file']['size'] > 2 * 1024 * 1024) {
        echo "<div style='color:red'>Ukuran maksimal 2MB.</div>";
        exit;
    }

    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        echo "<div style='color:green'>✅ Upload berhasil ke: $target</div>";
        // Simpan ke database kalau perlu
    } else {
        echo "<div style='color:red'>❌ Upload gagal.</div>";
    }
}
?>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button type="submit">Upload</button>
</form>
