<?php
require_once "auth.php";
require_once "config.php";
require_once "bahasa.php";

// Mengamankan output
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Mengambil ID laporan dari URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: barang.php");
    exit;
}

// Mengambil detail laporan berdasarkan ID
$stmt = $conn->prepare(
    "SELECT id, nama_barang, kategori, jenis_laporan,
            lokasi, tanggal_kejadian, deskripsi,
            status, tanggal_laporan
     FROM barang
     WHERE id = ?"
);

if (!$stmt) {
    http_response_code(500);
    exit("Terjadi kesalahan saat memproses data.");
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$barang = $result->fetch_assoc();

$stmt->close();

// Jika laporan tidak ditemukan
if (!$barang) {
    http_response_code(404);
    exit(
        $lang === "en"
        ? "Report not found. <a href='barang.php'>Back to reports</a>"
        : "Laporan tidak ditemukan. <a href='barang.php'>Kembali ke data barang</a>"
    );
}

// Format tanggal
$tanggal_kejadian = date(
    "d-m-Y",
    strtotime($barang["tanggal_kejadian"])
);

$tanggal_laporan = date(
    "d-m-Y H:i",
    strtotime($barang["tanggal_laporan"])
);

// Terjemahan jenis laporan
$jenis_en = [
    "Hilang" => "Lost",
    "Ditemukan" => "Found"
];

// Terjemahan status
$status_en = [
    "Belum Ditemukan" => "Not Found Yet",
    "Belum Diklaim" => "Not Yet Claimed",
    "Sudah Dikembalikan" => "Returned"
];

$judul = $lang === "en"
    ? "Report Details"
    : "Detail Laporan Barang";

?>

<!DOCTYPE html>
<html lang="<?= e($lang === "en" ? "en" : "id") ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($judul) ?> | CampusFind</title>

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

        .subtitle {
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

        .menu-link:hover,
        .menu-link.active {
            background: #2563eb;
            color: white;
        }

        .main {
            margin-left: 245px;
            padding: 30px;
        }

        .topbar {
            margin-bottom: 25px;
        }

        .topbar h1 {
            margin: 0 0 8px;
            font-size: 27px;
        }

        .topbar p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .detail-box {
            background: white;
            padding: 28px;
            border-radius: 14px;
            border: 1px solid #e8edf5;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
            max-width: 900px;
        }

        .detail-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #edf0f5;
        }

        .detail-heading h2 {
            margin: 0;
            font-size: 21px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .detail-item {
            padding: 16px;
            background: #f8fafc;
            border-radius: 10px;
            overflow-wrap: anywhere;
        }

        .detail-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 9px;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .badge {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .hilang {
            background: #fef3c7;
            color: #92400e;
        }

        .ditemukan {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .button {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            border: none;
            cursor: pointer;
        }

        .button-back {
            background: #e2e8f0;
            color: #334155;
        }

        .button-edit {
            background: #2563eb;
            color: white;
        }

        .button:hover {
            opacity: 0.85;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 30px;
        }

        @media (max-width: 700px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .subtitle {
                margin-bottom: 15px;
            }

            .main {
                margin-left: 0;
                padding: 20px 15px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }

            .topbar h1 {
                font-size: 23px;
            }

            .detail-box {
                padding: 18px;
            }
        }
        </style>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<aside class="sidebar">

    <div class="brand">CampusFind</div>

    <p class="subtitle">
        Lost &amp; Found Management System
    </p>

    <div class="menu-title">
        <?= $lang === "en" ? "MAIN MENU" : "MENU UTAMA" ?>
    </div>

    <a href="index.php" class="menu-link">
        ▦ <?= e($teks[$lang]["dashboard"]) ?>
    </a>

    <a href="barang.php" class="menu-link active">
        ▤ <?= e($teks[$lang]["data_barang"]) ?>
    </a>

    <div class="menu-title">
        <?= $lang === "en" ? "ACCOUNT" : "AKUN" ?>
    </div>

    <a href="logout.php" class="menu-link">
        ↪ <?= e($teks[$lang]["logout"]) ?>
    </a>

</aside>

<main class="main">

    <header class="topbar">
        <h1><?= e($judul) ?></h1>

        <p>
            <?= $lang === "en"
                ? "Complete information about the selected item report."
                : "Informasi lengkap mengenai laporan barang yang dipilih." ?>
        </p>
    </header>

    <section class="detail-box">

        <div class="detail-heading">

            <h2>
                <?= e($barang["nama_barang"]) ?>
                (#<?= (int) $barang["id"] ?>)
            </h2>

            <div>
                <a href="barang.php" class="button button-back">
                    ← <?= $lang === "en" ? "Back" : "Kembali" ?>
                </a>

                <a
                    href="edit.php?id=<?= (int) $barang["id"] ?>"
                    class="button button-edit"
                >
                    <?= $lang === "en" ? "Edit Report" : "Edit Laporan" ?>
                </a>
            </div>

        </div>

        <div class="detail-grid">

            <div class="detail-item">
                <div class="detail-label">
                    <?= $lang === "en" ? "Item Name" : "Nama Barang" ?>
                </div>

                <div class="detail-value">
                    <?= e($barang["nama_barang"]) ?>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <?= $lang === "en" ? "Category" : "Kategori" ?>
                </div>

                <div class="detail-value">
                    <?= e($barang["kategori"]) ?>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <?= $lang === "en" ? "Report Type" : "Jenis Laporan" ?>
                </div>

                <div class="detail-value">
                    <?php
                    $jenis = $barang["jenis_laporan"];

                    if ($lang === "en") {
                        $jenis = $jenis_en[$jenis] ?? $jenis;
                    }
                    ?>

                    <span class="badge <?= $barang["jenis_laporan"] === "Hilang" ? "hilang" : "ditemukan" ?>">
                        <?= e($jenis) ?>
                    </span>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <?= $lang === "en" ? "Status" : "Status Barang" ?>
                </div>

                <div class="detail-value">
                    <?php
                    $status = $barang["status"];

                    if ($lang === "en") {
                        $status = $status_en[$status] ?? $status;
                    }

                    echo e($status);
                    ?>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <?= $lang === "en" ? "Incident Location" : "Lokasi Kejadian" ?>
                </div>

                <div class="detail-value">
                    <?= e($barang["lokasi"]) ?>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">
                    <?= $lang === "en" ? "Incident Date" : "Tanggal Kejadian" ?>
                </div>

                <div class="detail-value">
                    <?= e($tanggal_kejadian) ?>
                </div>
            </div>

            <div class="detail-item full-width">
                <div class="detail-label">
                    <?= $lang === "en" ? "Description" : "Deskripsi" ?>
                </div>

                <div class="detail-value"><?=
                    trim((string) $barang["deskripsi"]) !== ""
                        ? e($barang["deskripsi"])
                        : ($lang === "en"
                            ? "No description provided."
                            : "Tidak ada deskripsi.")
                ?></div>
            </div>

            <div class="detail-item full-width">
                <div class="detail-label">
                    <?= $lang === "en" ? "Report Created" : "Tanggal Laporan Dibuat" ?>
                </div>

                <div class="detail-value">
                    <?= e($tanggal_laporan) ?>
                </div>
            </div>

        </div>

    </section>

    <div class="footer">
        &copy; <?= date("Y") ?> CampusFind
    </div>

</main>

</body>
</html>