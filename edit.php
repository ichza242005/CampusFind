<?php
require_once "auth.php";
require_once "config.php";
require_once "bahasa.php";

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: barang.php");
    exit;
}

// Mengambil data laporan berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM barang WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$barang = $result->fetch_assoc();
$stmt->close();

if (!$barang) {
    http_response_code(404);
    exit("Data laporan tidak ditemukan.");
}

$error = "";

$nama_barang = $barang["nama_barang"];
$kategori = $barang["kategori"];
$jenis_laporan = $barang["jenis_laporan"];
$lokasi = $barang["lokasi"];
$tanggal_kejadian = $barang["tanggal_kejadian"];
$deskripsi = $barang["deskripsi"];
$status = $barang["status"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = "Token keamanan tidak valid. Muat ulang halaman.";
    } else {
        $nama_barang = trim($_POST["nama_barang"] ?? "");
        $kategori = trim($_POST["kategori"] ?? "");
        $jenis_laporan = $_POST["jenis_laporan"] ?? "";
        $lokasi = trim($_POST["lokasi"] ?? "");
        $tanggal_kejadian = $_POST["tanggal_kejadian"] ?? "";
        $deskripsi = trim($_POST["deskripsi"] ?? "");
        $status = $_POST["status"] ?? "";

        $kategori_valid = [
            "Elektronik", "Dokumen", "Aksesori",
            "Pakaian", "Alat Tulis", "Lainnya"
        ];

        $status_valid = [
            "Belum Ditemukan",
            "Belum Diklaim",
            "Sudah Dikembalikan"
        ];

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
            $tanggal_kejadian === ""
        ) {
            $error = "Semua kolom wajib diisi kecuali deskripsi.";

        } elseif (
            strlen($nama_barang) > 100 ||
            strlen($lokasi) > 150 ||
            !in_array($kategori, $kategori_valid, true)
        ) {
            $error = "Data tidak valid atau melebihi batas karakter.";

        } elseif (
            !in_array($jenis_laporan, ["Hilang", "Ditemukan"], true) ||
            !in_array($status, $status_valid, true)
        ) {
            $error = "Jenis laporan atau status tidak valid.";

        } elseif (
            ($jenis_laporan === "Hilang" && $status === "Belum Diklaim") ||
            ($jenis_laporan === "Ditemukan" && $status === "Belum Ditemukan")
        ) {
            $error = "Status tidak sesuai dengan jenis laporan.";

        } elseif (!$tanggal_valid) {
            $error = "Tanggal kejadian tidak valid atau melebihi hari ini.";

        } else {
            $stmt = $conn->prepare(
                "UPDATE barang
                 SET nama_barang = ?,
                     kategori = ?,
                     jenis_laporan = ?,
                     lokasi = ?,
                     tanggal_kejadian = ?,
                     deskripsi = ?,
                     status = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                "sssssssi",
                $nama_barang,
                $kategori,
                $jenis_laporan,
                $lokasi,
                $tanggal_kejadian,
                $deskripsi,
                $status,
                $id
            );

            if ($stmt->execute()) {
                $stmt->close();

                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                header("Location: barang.php?berhasil=edit");
                exit;
            }

            $error = "Data gagal diperbarui. Silakan coba kembali.";
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

    <title><?= $lang === "id" ? "Edit Laporan" : "Edit Report" ?> | CampusFind</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fc;
            color: #1e293b;
        }

        .sidebar {
            width: 245px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #172554;
            color: white;
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

        .content-box {
            max-width: 900px;
            padding: 28px;
            background: white;
            border: 1px solid #e8edf5;
            border-radius: 14px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        h1 { margin: 0 0 8px; font-size: 27px; }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .profile {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 10px 14px;
            font-size: 13px;
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

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .full { grid-column: 1 / -1; }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
            font-family: inherit;
            font-size: 13px;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #2563eb;
        }

        .error {
            padding: 12px;
            border-radius: 8px;
            color: #b91c1c;
            background: #fef2f2;
            border: 1px solid #fecaca;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .button {
            display: inline-block;
            border: none;
            border-radius: 8px;
            padding: 12px 17px;
            color: white;
            background: #2563eb;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
        }

        .button-gray { background: #64748b; }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 20px 15px;
            }

            .form-grid { grid-template-columns: 1fr; }
            .full { grid-column: auto; }
            .content-box { padding: 20px; }
        }
     </style>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<aside class="sidebar">
    <div class="brand">CampusFind</div>
    <p class="brand-subtitle">Lost &amp; Found Management System</p>

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
            <h1><?= $lang === "id" ? "Edit Laporan Barang" : "Edit Item Report" ?></h1>
            <p class="subtitle">
                <?= $lang === "id"
                    ? "Perbarui informasi laporan barang."
                    : "Update the item report information." ?>
            </p>
        </div>

        <div class="top-actions">
            <div class="language">
                <a href="edit.php?id=<?= $id ?>&lang=id"
                   class="<?= $lang === "id" ? "active" : "" ?>">ID</a>
                <a href="edit.php?id=<?= $id ?>&lang=en"
                   class="<?= $lang === "en" ? "active" : "" ?>">EN</a>
            </div>

            <div class="profile"><?= e($nama_admin) ?></div>
        </div>
    </header>

    <section class="content-box">

        <?php if ($error !== ""): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="edit.php?id=<?= $id ?>">

            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">

            <div class="form-grid">

                <div>
                    <label for="nama_barang">
                        <?= $lang === "id" ? "Nama Barang *" : "Item Name *" ?>
                    </label>
                    <input type="text" id="nama_barang" name="nama_barang"
                           maxlength="100" required value="<?= e($nama_barang) ?>">
                </div>

                <div>
                    <label for="kategori">
                        <?= $lang === "id" ? "Kategori *" : "Category *" ?>
                    </label>

                    <select id="kategori" name="kategori" required>
                        <?php
                        $kategori_list = [
                            "Elektronik", "Dokumen", "Aksesori",
                            "Pakaian", "Alat Tulis", "Lainnya"
                        ];
                        foreach ($kategori_list as $item):
                        ?>
                            <option value="<?= e($item) ?>"
                                <?= $kategori === $item ? "selected" : "" ?>>
                                <?= e($item) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="jenis_laporan">
                        <?= $lang === "id" ? "Jenis Laporan *" : "Report Type *" ?>
                    </label>

                    <select id="jenis_laporan" name="jenis_laporan" required>
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

                <div>
                    <label for="tanggal_kejadian">
                        <?= $lang === "id" ? "Tanggal Kejadian *" : "Incident Date *" ?>
                    </label>

                    <input type="date" id="tanggal_kejadian"
                           name="tanggal_kejadian"
                           max="<?= date("Y-m-d") ?>"
                           required value="<?= e($tanggal_kejadian) ?>">
                </div>

                <div class="full">
                    <label for="lokasi">
                        <?= $lang === "id" ? "Lokasi Kejadian *" : "Incident Location *" ?>
                    </label>

                    <input type="text" id="lokasi" name="lokasi"
                           maxlength="150" required value="<?= e($lokasi) ?>">
                </div>

                <div class="full">
                    <label for="deskripsi">
                        <?= $lang === "id" ? "Deskripsi Barang" : "Item Description" ?>
                    </label>

                    <textarea id="deskripsi" name="deskripsi"><?= e($deskripsi) ?></textarea>
                </div>

                <div class="full">
                    <label for="status">
                        <?= $lang === "id" ? "Status Barang *" : "Item Status *" ?>
                    </label>

                    <select id="status" name="status" required>
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

            <div class="actions">
                <button type="submit" class="button">
                    <?= $lang === "id" ? "Simpan Perubahan" : "Save Changes" ?>
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
    const statusLama = status.value;

    status.querySelector('option[value="Belum Ditemukan"]').hidden =
        jenis.value === "Ditemukan";

    status.querySelector('option[value="Belum Diklaim"]').hidden =
        jenis.value === "Hilang";

    if (
        (jenis.value === "Hilang" && statusLama === "Belum Diklaim") ||
        (jenis.value === "Ditemukan" && statusLama === "Belum Ditemukan")
    ) {
        status.value = "";
    }
}

jenis.addEventListener("change", sesuaikanStatus);
sesuaikanStatus();
</script>

</body>
</html>