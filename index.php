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

// Menghitung total laporan
$result_total = $conn->query(
    "SELECT COUNT(*) AS total FROM barang"
);
$total_laporan = (int) $result_total->fetch_assoc()["total"];

// Menghitung barang hilang
$result_hilang = $conn->query(
    "SELECT COUNT(*) AS total
     FROM barang
     WHERE jenis_laporan = 'Hilang'"
);
$total_hilang = (int) $result_hilang->fetch_assoc()["total"];

// Menghitung barang ditemukan
$result_ditemukan = $conn->query(
    "SELECT COUNT(*) AS total
     FROM barang
     WHERE jenis_laporan = 'Ditemukan'"
);
$total_ditemukan = (int) $result_ditemukan->fetch_assoc()["total"];

// Menghitung barang yang sudah dikembalikan
$result_kembali = $conn->query(
    "SELECT COUNT(*) AS total
     FROM barang
     WHERE status = 'Sudah Dikembalikan'"
);
$total_kembali = (int) $result_kembali->fetch_assoc()["total"];

// Mengambil beberapa laporan terbaru
$result_terbaru = $conn->query(
    "SELECT id, nama_barang, kategori, jenis_laporan,
            lokasi, tanggal_kejadian, status
     FROM barang
     ORDER BY tanggal_laporan DESC
     LIMIT 5"
);

$nama_admin = $_SESSION["nama_lengkap"] ?? "Administrator";
?>

<!DOCTYPE html>
<html lang="<?= e($lang === 'en' ? 'en' : 'id') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($teks[$lang]["dashboard"]) ?> | CampusFind</title>

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
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
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

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
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

        .section-title {
            font-size: 18px;
            margin: 0 0 18px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border: 1px solid #e8edf5;
            padding: 23px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
        }

        .card-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 14px;
        }

        .card-number {
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .card-note {
            font-size: 12px;
            color: #64748b;
        }

        .blue {
            border-top: 4px solid #2563eb;
        }

        .orange {
            border-top: 4px solid #f59e0b;
        }

        .green {
            border-top: 4px solid #10b981;
        }

        .purple {
            border-top: 4px solid #8b5cf6;
        }

        .content-box {
            background: white;
            padding: 24px;
            border-radius: 14px;
            border: 1px solid #e8edf5;
            margin-bottom: 25px;
        }

        .box-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .box-heading h2 {
            margin: 0;
            font-size: 17px;
        }

        .button {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
        }

        .button:hover {
            background: #1d4ed8;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #edf0f5;
            font-size: 13px;
        }

        th {
            color: #64748b;
            font-weight: bold;
            background: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 11px;
            background: #e2e8f0;
            color: #334155;
            white-space: nowrap;
        }

        .badge.hilang {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.ditemukan {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 30px;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 30px;
        }

        @media (max-width: 1100px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .brand-subtitle {
                margin-bottom: 15px;
            }

            .main {
                margin-left: 0;
                padding: 20px 15px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .topbar h1 {
                font-size: 23px;
            }
        }
    </style>

    <link rel="stylesheet" href="assets/style.css">
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

    <a href="index.php" class="menu-link active">
        ▦ <?= e($teks[$lang]["dashboard"]) ?>
    </a>

    <a href="barang.php" class="menu-link">
        ▤ <?= e($teks[$lang]["data_barang"]) ?>
    </a>

    <div class="menu-title">
        Akun
    </div>

    <a href="logout.php" class="menu-link">
        ↪ <?= e($teks[$lang]["logout"]) ?>
    </a>

</aside>

<main class="main">

    <header class="topbar">

        <div>
            <h1>
                <?= e($teks[$lang]["selamat_datang"]) ?>,
                <?= e($nama_admin) ?>!
            </h1>

            <p>
                <?= e($teks[$lang]["deskripsi_dashboard"]) ?>
            </p>
        </div>

        <div class="top-actions">

            <div class="language">
                <a
                    href="index.php?lang=id"
                    class="<?= $lang === 'id' ? 'active' : '' ?>"
                >
                    ID
                </a>

                <a
                    href="index.php?lang=en"
                    class="<?= $lang === 'en' ? 'active' : '' ?>"
                >
                    EN
                </a>
            </div>

            <div class="profile">
                <?= e($teks[$lang]["admin"]) ?>
            </div>

        </div>

    </header>

    <h2 class="section-title">
        <?= e($teks[$lang]["ringkasan"]) ?>
    </h2>

    <section class="cards">

        <div class="card blue">
            <div class="card-label">
                <?= e($teks[$lang]["total_laporan"]) ?>
            </div>

            <div class="card-number">
                <?= $total_laporan ?>
            </div>

            <div class="card-note">
                CampusFind
            </div>
        </div>

        <div class="card orange">
            <div class="card-label">
                <?= e($teks[$lang]["barang_hilang"]) ?>
            </div>

            <div class="card-number">
                <?= $total_hilang ?>
            </div>

            <div class="card-note">
                <?= e($teks[$lang]["belum_ditemukan"]) ?>
            </div>
        </div>

        <div class="card purple">
            <div class="card-label">
                <?= e($teks[$lang]["barang_ditemukan"]) ?>
            </div>

            <div class="card-number">
                <?= $total_ditemukan ?>
            </div>

            <div class="card-note">
                <?= e($teks[$lang]["belum_diklaim"]) ?>
            </div>
        </div>

        <div class="card green">
            <div class="card-label">
                <?= e($teks[$lang]["barang_kembali"]) ?>
            </div>

            <div class="card-number">
                <?= $total_kembali ?>
            </div>

            <div class="card-note">
                CampusFind
            </div>
        </div>

    </section>

    <section class="content-box">

        <div class="box-heading">

            <h2>
                <?= e($teks[$lang]["data_barang"]) ?>
            </h2>

            <a href="barang.php" class="button">
                <?= e($teks[$lang]["lihat_data"]) ?> →
            </a>

        </div>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= $lang === 'id' ? 'Nama Barang' : 'Item Name' ?></th>
                        <th><?= $lang === 'id' ? 'Kategori' : 'Category' ?></th>
                        <th><?= $lang === 'id' ? 'Jenis Laporan' : 'Report Type' ?></th>
                        <th><?= $lang === 'id' ? 'Tanggal Kejadian' : 'Incident Date' ?></th>
                        <th><?= $lang === 'id' ? 'Status' : 'Status' ?></th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result_terbaru && $result_terbaru->num_rows > 0): ?>

                    <?php while ($row = $result_terbaru->fetch_assoc()): ?>

                        <tr>
                            <td><?= (int) $row["id"] ?></td>

                            <td><?= e($row["nama_barang"]) ?></td>

                            <td><?= e($row["kategori"]) ?></td>

                            <td>
                                <span class="badge <?= $row["jenis_laporan"] === 'Hilang' ? 'hilang' : 'ditemukan' ?>">
                                    <?php
                                    if ($lang === 'en') {
                                        echo $row["jenis_laporan"] === 'Hilang'
                                            ? 'Lost'
                                            : 'Found';
                                    } else {
                                        echo e($row["jenis_laporan"]);
                                    }
                                    ?>
                                </span>
                            </td>

                            <td>
                                <?= e(date(
                                    "d-m-Y",
                                    strtotime($row["tanggal_kejadian"])
                                )) ?>
                            </td>

                            <td>
                                <?php
                                $status_en = [
                                    "Belum Ditemukan" => "Not Found Yet",
                                    "Belum Diklaim" => "Not Yet Claimed",
                                    "Sudah Dikembalikan" => "Returned"
                                ];

                                echo e(
                                    $lang === 'en'
                                    ? ($status_en[$row["status"]] ?? $row["status"])
                                    : $row["status"]
                                );
                                ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty">
                            <?= $lang === 'id'
                                ? 'Belum ada laporan barang. Silakan tambahkan data melalui menu Data Barang.'
                                : 'There are no item reports yet. Add a report through the Item Reports menu.' ?>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <div class="footer">
        &copy; <?= date("Y") ?> CampusFind
    </div>

</main>

</body>
</html>