<?php
// delete_peserta.php

// Include database connection and authentication
include "config/db.php";
include "config/auth.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php?message=Access+denied&messageType=error");
    exit;
}

// Check if ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: peserta.php?message=Invalid+or+missing+ID&messageType=error");
    exit;
}

$peserta_id = (int)$_GET['id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Fetch participant data to get photo filename
    $query = "SELECT foto FROM peserta WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $peserta_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $peserta = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$peserta) {
        throw new Exception("Peserta not found");
    }

    // Delete associated photo file if exists
    if ($peserta['foto'] && file_exists("Uploads/peserta/" . $peserta['foto'])) {
        unlink("Uploads/peserta/" . $peserta['foto']);
    }

    // Delete from arsip table if exists
    $query = "DELETE FROM arsip WHERE peserta_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $peserta_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Delete from peserta table
    $query = "DELETE FROM peserta WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $peserta_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($affected_rows === 0) {
        throw new Exception("Failed to delete peserta");
    }

    // Commit transaction
    mysqli_commit($conn);

    // Redirect with success message
    header("Location: peserta.php?message=Peserta+berhasil+dihapus&messageType=success");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);

    // Redirect with error message
    header("Location: peserta.php?message=Gagal+menghapus+peserta:+" . urlencode($e->getMessage()) . "&messageType=error");
    exit;
}

// Close database connection
mysqli_close($conn);
?>