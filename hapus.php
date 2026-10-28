<?php
require_once "auth.php";
require_once "config.php";

// Penghapusan hanya diperbolehkan melalui POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: barang.php");
    exit;
}

// Validasi token keamanan
$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

// Validasi ID
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: barang.php");
    exit;
}

// Hapus laporan berdasarkan ID
$stmt = $conn->prepare("DELETE FROM barang WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION["pesan"] = "Laporan barang berhasil dihapus.";
} else {
    $_SESSION["pesan"] = "Data tidak ditemukan atau sudah dihapus.";
}

$stmt->close();

// Buat token baru setelah operasi
$_SESSION["csrf_token"] = bin2hex(random_bytes(32));

header("Location: barang.php");
exit;
?>