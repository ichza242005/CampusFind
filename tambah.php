<?php
require_once "auth.php";
require_once "config.php";
require_once "bahasa.php";

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Membuat token keamanan
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

$nama_barang = "";
$kategori = "";
$jenis_laporan = "";
$lokasi = "";
$tanggal_kejadian = "";
$deskripsi = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = $lang === "id"
            ? "Permintaan tidak valid. Silakan muat ulang halaman."
            : "Invalid request. Please reload the page.";
    } else {

        $nama_barang = trim($_POST["nama_barang"] ?? "");
        $kategori = trim($_POST["kategori"] ?? "");
        $jenis_laporan = $_POST["jenis_laporan"] ?? "";
        $lokasi = trim($_POST["lokasi"] ?? "");
        $tanggal_kejadian = $_POST["tanggal_kejadian"] ?? "";
        $deskripsi = trim($_POST["deskripsi"] ?? "");
        $status = $_POST["status"] ?? "";

        // Validasi pilihan yang diperbolehkan
        $jenis_valid = ["Hilang", "Ditemukan"];

        $status_valid = [
            "Belum Ditemukan",
            "Belum Diklaim",
            "Sudah Dikembalikan"
        ];

        // Validasi tanggal dengan format YYYY-MM-DD
        $tanggal_obj = DateTime::createFromFormat(
            "!Y-m-d",
            $tanggal_kejadian
        );

        $tanggal_valid = $tanggal_obj &&
            $tanggal_obj->format("Y-m-d") === $tanggal_kejadian &&
            $tanggal_kejadian <= date("Y-m-d");

        if (
            $nama_barang === "" ||
            $kategori === "" ||
            $lokasi === "" ||
            $tanggal_kejadian === "" ||
            $jenis_laporan === "" ||
            $status === ""
        ) {
            $error = $lang === "id"
                ? "Semua kolom wajib diisi kecuali deskripsi."
                : "All fields are required except description.";

        } elseif (
            strlen($nama_barang) > 100 ||
            strlen($kategori) > 50 ||
            strlen($lokasi) > 150
        ) {
            $error = $lang === "id"
                ? "Nama barang, kategori, atau lokasi terlalu panjang."
                : "The item name, category, or location is too long.";

        } elseif (
            !in_array($jenis_laporan, $jenis_valid, true) ||
            !in_array($status, $status_valid, true)
        ) {
            $error = $lang === "id"
                ? "Jenis laporan atau status tidak valid."
                : "The report type or status is invalid.";

        } elseif (!$tanggal_valid) {
            $error = $lang === "id"
                ? "Tanggal kejadian tidak valid atau tidak boleh melebihi hari ini."
                : "The incident date is invalid or cannot be in the future.";

        } elseif (
            ($jenis_laporan === "Hilang" && $status === "Belum Diklaim") ||
            ($jenis_laporan === "Ditemukan" && $status === "Belum Ditemukan")
        ) {
            $error = $lang === "id"
                ? "Status tidak sesuai dengan jenis laporan."
                : "The status does not match the report type.";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO barang
                (nama_barang, kategori, jenis_laporan, lokasi,
                 tanggal_kejadian, deskripsi, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssssss",
                $nama_barang,
                $kategori,
                $jenis_laporan,
                $lokasi,
                $tanggal_kejadian,
                $deskripsi,
                $status
            );

            if ($stmt->execute()) {
                $stmt->close();

                // Ganti token setelah operasi berhasil
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                header("Location: barang.php?berhasil=tambah");
                exit;
            }

            $error = $lang === "id"
                ? "Data gagal disimpan. Silakan coba kembali."
                : "Failed to save the report. Please try again.";

            $stmt->close();
        }
    }
}

$nama_admin = $_SESSION["nama_lengkap"] ?? "Administrator";
?>

<!DOCTYPE html>
<html lang="<?= e($lang === "en" ? "en" : "id") ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $lang === "id" ? "Tambah Laporan" : "Add Report" ?> | CampusFind
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fc;
            color: #1e293b;
        }

        .sidebar {
            width: 245px;
            height: 100vh;
            background: #172554;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            padding: 28px 18px;
        }

        .brand {
            font-size: 25px;
            font-weight: bold;
            margin: 0 0 8px 8px;
        }

        .brand-subtitle {
            color: #bfdbfe;
            font-size: 12px;
            margin: 0 8px 35px;
            line-height: 1.6;
        }

        .menu-title {
            color: #93c5fd;
            font-size: 11px;
            font-weight: bold;
            margin: 20px 8px 12px;
            text-transform: uppercase;
        }

        .menu-link {
            display: block;
            padding: 13px 14px;
            margin-bottom: 8px;
            border-radius: 9px;
            text-decoration: none;
            color: #e2e8f0;
            font-size: 14px;
        }

        .menu-link.active,
        .menu-link:hover {
            background: #2563eb;
            color: white;
        }

        .main {
            margin-left: 245px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        h1 {
            font-size: 27px;
            margin: 0 0 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin: 0;
            line-height: 1.6;
        }

        .language {
            display: flex;
            gap: 5px;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 5px;
            border-radius: 9px;
        }

        .language a {
            padding: 8px 10px;
            border-radius: 6px;
            text-decoration: none;
            color: #475569;
            font-size: 12px;
        }

        .language a.active {
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: bold;
        }

        .profile {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 10px 14px;
            font-size: 13px;
        }

        .top-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .content-box {
            background: white;
            padding: 28px;
            border-radius: 14px;
            border: 1px solid #e8edf5;
            max-width: 950px;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 3px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: bold;
            color: #334155;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .button {
            display: inline-block;
            border: none;
            background: #2563eb;
            color: white;
            padding: 12px 17px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
        }

        .button:hover {
            background: #1d4ed8;
        }

        .button-gray {
            background: #64748b;
        }

        .button-gray:hover {
            background: #475569;
        }

        @media (max-width: 700px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main {
                margin-left: 0;
                padding: 20px 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }

            .content-box {
                padding: 20px;
            }

            h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">

    <div class="brand">CampusFind</div>

    <p class="brand-subtitle">
        Lost &amp; Found Management System
    </p>

    <div class="menu-title">
        <?= e($teks[$lang]["menu_utama"]) ?>
    </div>

    <a href="index.php" class="menu-link">
        ▦ <?= e($teks[$lang]["dashboard"]) ?>
    </a>

    <a href="barang.php" class="menu-link active">
        ▤ <?= e($teks[$lang]["data_barang"]) ?>
    </a>

    <div class="menu-title">Akun</div>

    <a href="logout.php" class="menu-link">
        ↪ <?= e($teks[$lang]["logout"]) ?>
    </a>

</aside>

<main class="main">

    <header class="topbar">

        <div>
            <h1>
                <?= $lang === "id" ? "Tambah Laporan Barang" : "Add Item Report" ?>
            </h1>

            <p class="subtitle">
                <?= $lang === "id"
                    ? "Masukkan informasi barang hilang atau barang yang ditemukan."
                    : "Enter the details of a lost or found item." ?>
            </p>
        </div>

        <div class="top-actions">

            <div class="language">
                <a href="tambah.php?lang=id"
                   class="<?= $lang === "id" ? "active" : "" ?>">ID</a>

                <a href="tambah.php?lang=en"
                   class="<?= $lang === "en" ? "active" : "" ?>">EN</a>
            </div>

            <div class="profile"><?= e($nama_admin) ?></div>

        </div>

    </header>

    <section class="content-box">

        <?php if ($error !== ""): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="tambah.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION["csrf_token"]) ?>"
            >

            <div class="form-grid">

                <div class="form-group">
                    <label for="nama_barang">
                        <?= $lang === "id" ? "Nama Barang *" : "Item Name *" ?>
                    </label>

                    <input
                        type="text"
                        id="nama_barang"
                        name="nama_barang"
                        maxlength="100"
                        value="<?= e($nama_barang) ?>"
                        placeholder="<?= $lang === "id" ? "Contoh: Dompet hitam" : "Example: Black wallet" ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="kategori">
                        <?= $lang === "id" ? "Kategori *" : "Category *" ?>
                    </label>

                    <select id="kategori" name="kategori" required>
                        <option value="">
                            <?= $lang === "id" ? "-- Pilih Kategori --" : "-- Select Category --" ?>
                        </option>

                        <?php
                        $kategori_list = [
                            "Elektronik",
                            "Dokumen",
                            "Aksesori",
                            "Pakaian",
                            "Alat Tulis",
                            "Lainnya"
                        ];
                        ?>

                        <?php foreach ($kategori_list as $item): ?>
                            <option
                                value="<?= e($item) ?>"
                                <?= $kategori === $item ? "selected" : "" ?>
                            >
                                <?= e($lang === "en" ? [
                                    "Elektronik" => "Electronics",
                                    "Dokumen" => "Documents",
                                    "Aksesori" => "Accessories",
                                    "Pakaian" => "Clothing",
                                    "Alat Tulis" => "Stationery",
                                    "Lainnya" => "Other"
                                ][$item] : $item) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="jenis_laporan">
                        <?= $lang === "id" ? "Jenis Laporan *" : "Report Type *" ?>
                    </label>

                    <select
                        id="jenis_laporan"
                        name="jenis_laporan"
                        required
                    >
                        <option value="">
                            <?= $lang === "id" ? "-- Pilih Jenis --" : "-- Select Type --" ?>
                        </option>

                        <option value="Hilang"
                            <?= $jenis_laporan === "Hilang" ? "selected" : "" ?>>
                            <?= $lang === "id" ? "Barang Hilang" : "Lost Item" ?>
                        </option>

                        <option value="Ditemukan"
                            <?= $jenis_laporan === "Ditemukan" ? "selected" : "" ?>>
                            <?= $lang === "id" ? "Barang Ditemukan" : "Found Item" ?>
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tanggal_kejadian">
                        <?= $lang === "id" ? "Tanggal Kejadian *" : "Incident Date *" ?>
                    </label>

                    <input
                        type="date"
                        id="tanggal_kejadian"
                        name="tanggal_kejadian"
                        max="<?= date("Y-m-d") ?>"
                        value="<?= e($tanggal_kejadian) ?>"
                        required
                    >
                </div>

                <div class="form-group full-width">
                    <label for="lokasi">
                        <?= $lang === "id" ? "Lokasi Kejadian *" : "Incident Location *" ?>
                    </label>

                    <input
                        type="text"
                        id="lokasi"
                        name="lokasi"
                        maxlength="150"
                        value="<?= e($lokasi) ?>"
                        placeholder="<?= $lang === "id"
                            ? "Contoh: Perpustakaan lantai 2"
                            : "Example: Library, second floor" ?>"
                        required
                    >
                </div>

                <div class="form-group full-width">
                    <label for="deskripsi">
                        <?= $lang === "id" ? "Deskripsi Barang" : "Item Description" ?>
                    </label>

                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        placeholder="<?= $lang === "id"
                            ? "Tuliskan ciri-ciri barang atau informasi tambahan..."
                            : "Describe the item or provide additional information..." ?>"
                    ><?= e($deskripsi) ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="status">
                        <?= $lang === "id" ? "Status Barang *" : "Item Status *" ?>
                    </label>

                    <select id="status" name="status" required>
                        <option value="">
                            <?= $lang === "id" ? "-- Pilih Status --" : "-- Select Status --" ?>
                        </option>

                        <option value="Belum Ditemukan"
                            <?= $status === "Belum Ditemukan" ? "selected" : "" ?>>
                            <?= $lang === "id" ? "Belum Ditemukan" : "Not Found Yet" ?>
                        </option>

                        <option value="Belum Diklaim"
                            <?= $status === "Belum Diklaim" ? "selected" : "" ?>>
                            <?= $lang === "id" ? "Belum Diklaim" : "Not Yet Claimed" ?>
                        </option>

                        <option value="Sudah Dikembalikan"
                            <?= $status === "Sudah Dikembalikan" ? "selected" : "" ?>>
                            <?= $lang === "id" ? "Sudah Dikembalikan" : "Returned" ?>
                        </option>
                    </select>
                </div>

            </div>

            <div class="form-actions">

                <button type="submit" class="button">
                    <?= $lang === "id" ? "Simpan Laporan" : "Save Report" ?>
                </button>

                <a href="barang.php" class="button button-gray">
                    <?= $lang === "id" ? "Kembali" : "Back" ?>
                </a>

            </div>

        </form>

    </section>

</main>

<script>
const jenis = document.getElementById("jenis_laporan");
const status = document.getElementById("status");

function sesuaikanStatus() {
    const nilaiJenis = jenis.value;
    const pilihan = status.options;

    for (let i = 0; i < pilihan.length; i++) {
        pilihan[i].hidden = false;
    }

    if (nilaiJenis === "Hilang") {
        status.querySelector('option[value="Belum Diklaim"]').hidden = true;
    } else if (nilaiJenis === "Ditemukan") {
        status.querySelector('option[value="Belum Ditemukan"]').hidden = true;
    }

    if (
        (nilaiJenis === "Hilang" && status.value === "Belum Diklaim") ||
        (nilaiJenis === "Ditemukan" && status.value === "Belum Ditemukan")
    ) {
        status.value = "";
    }
}

jenis.addEventListener("change", sesuaikanStatus);
sesuaikanStatus();
</script>

</body>
</html>