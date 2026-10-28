<?php
// Konfigurasi koneksi database

$host = "localhost";
$username = "root";
$password = "";
$database = "campusfind";

// Membuat koneksi menggunakan MySQLi
$conn = new mysqli($host, $username, $password, $database);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Mengatur karakter agar mendukung berbagai bahasa
$conn->set_charset("utf8mb4");
?>