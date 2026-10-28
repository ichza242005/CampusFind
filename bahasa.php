<?php
// Memulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bahasa default
if (!isset($_SESSION["bahasa"])) {
    $_SESSION["bahasa"] = "id";
}

// Mengganti bahasa melalui parameter URL
if (isset($_GET["lang"])) {
    $bahasa_pilihan = $_GET["lang"];

    if (in_array($bahasa_pilihan, ["id", "en"], true)) {
        $_SESSION["bahasa"] = $bahasa_pilihan;
    }

    // Kembali ke halaman saat ini tanpa parameter bahasa
    $halaman = basename($_SERVER["PHP_SELF"]);
    header("Location: " . $halaman);
    exit;
}

// Kamus bahasa aplikasi
$teks = [
    "id" => [
        "dashboard" => "Dashboard",
        "data_barang" => "Data Barang",
        "selamat_datang" => "Selamat Datang",
        "ringkasan" => "Ringkasan Data Barang",
        "barang_hilang" => "Barang Hilang",
        "barang_ditemukan" => "Barang Ditemukan",
        "barang_kembali" => "Sudah Dikembalikan",
        "total_laporan" => "Total Laporan",
        "menu_utama" => "Menu Utama",
        "kelola_data" => "Kelola Data Barang",
        "deskripsi_dashboard" => "Kelola laporan barang hilang dan temuan di lingkungan kampus dengan mudah.",
        "logout" => "Logout",
        "bahasa" => "Bahasa",
        "admin" => "Administrator",
        "lihat_data" => "Lihat Data Barang",
        "belum_ditemukan" => "Belum Ditemukan",
        "belum_diklaim" => "Belum Diklaim"
    ],
    "en" => [
        "dashboard" => "Dashboard",
        "data_barang" => "Item Reports",
        "selamat_datang" => "Welcome",
        "ringkasan" => "Item Report Summary",
        "barang_hilang" => "Lost Items",
        "barang_ditemukan" => "Found Items",
        "barang_kembali" => "Returned Items",
        "total_laporan" => "Total Reports",
        "menu_utama" => "Main Menu",
        "kelola_data" => "Manage Item Reports",
        "deskripsi_dashboard" => "Easily manage lost and found item reports on campus.",
        "logout" => "Logout",
        "bahasa" => "Language",
        "admin" => "Administrator",
        "lihat_data" => "View Item Reports",
        "belum_ditemukan" => "Not Found Yet",
        "belum_diklaim" => "Not Yet Claimed"
    ]
];

// Bahasa aktif
$lang = $_SESSION["bahasa"];
?>