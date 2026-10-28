<?php
require_once "auth.php";
require_once "config.php";
require_once "bahasa.php";

// Membuat token keamanan untuk formulir
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Mengambil kata kunci pencarian
$keyword = trim($_GET["cari"] ?? "");

// Mengambil data barang
if ($keyword !== "") {
    $search = "%" . $keyword . "%";

    $stmt = $conn->prepare(
        "SELECT * FROM barang
         WHERE nama_barang LIKE ?
            OR kategori LIKE ?
            OR lokasi LIKE ?
         ORDER BY tanggal_laporan DESC"
    );

    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query(
        "SELECT * FROM barang
         ORDER BY tanggal_laporan DESC"
    );
}

$jumlah_data = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="<?= e($lang === 'en' ? 'en' : 'id') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($teks[$lang]["data_barang"]) ?> | CampusFind</title>

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

        .top-actions {
            display: flex;
            gap: 12px;
            align-items: center;
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

        .content-box {
            background: white;
            padding: 24px;
            border-radius: 14px;
            border: 1px solid #e8edf5;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .toolbar h2 {
            font-size: 18px;
            margin: 0;
        }

        .search-form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        input[type="text"] {
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            min-width: 200px;
        }

        .button {
            display: inline-block;
            border: none;
            background: #2563eb;
            color: white;
            padding: 11px 14px;
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

        .button-edit {
            background: #dbeafe;
            color: #1d4ed8;
        }
		.button-detail {
			background: #dcfce7;
			color: #166534;
		}

		.button-detail:hover {
			background: #bbf7d0;
		}

        .button-edit:hover {
            background: #bfdbfe;
        }

        .button-delete {
            background: #fee2e2;
            color: #b91c1c;
        }

        .button-delete:hover {
            background: #fecaca;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th, td {
            text-align: left;
            padding: 13px 11px;
            border-bottom: 1px solid #edf0f5;
            font-size: 12px;
            vertical-align: middle;
        }

        th {
            color: #64748b;
            background: #f8fafc;
            white-space: nowrap;
        }

        .badge {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 11px;
            white-space: nowrap;
        }

        .lost {
            background: #fef3c7;
            color: #92400e;
        }

        .found {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status {
            background: #f1f5f9;
            color: #334155;
        }

        .actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .actions .button {
            padding: 8px 10px;
            font-size: 11px;
        }

        .actions form {
            margin: 0;
        }

        .empty {
            padding: 35px;
            text-align: center;
            color: #64748b;
        }

        .total {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 18px;
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

            h1 {
                font-size: 23px;
            }

            .search-form {
                width: 100%;
            }

            input[type="text"] {
                min-width: 0;
                flex: 1;
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
            <h1><?= e($teks[$lang]["data_barang"]) ?></h1>

            <p class="subtitle">
                <?= $lang === "id"
                    ? "Kelola seluruh laporan barang hilang dan temuan di kampus."
                    : "Manage all lost and found item reports on campus." ?>
            </p>
        </div>

        <div class="top-actions">

            <div class="language">
                <a
                    href="barang.php?lang=id"
                    class="<?= $lang === 'id' ? 'active' : '' ?>"
                >ID</a>

                <a
                    href="barang.php?lang=en"
                    class="<?= $lang === 'en' ? 'active' : '' ?>"
                >EN</a>
            </div>

            <div class="profile">
                <?= e($_SESSION["nama_lengkap"] ?? "Administrator") ?>
            </div>

        </div>

    </header>

    <section class="content-box">
	<?php
$pesan = $_SESSION["pesan"] ?? "";
unset($_SESSION["pesan"]);

$berhasil = $_GET["berhasil"] ?? "";
?>

<?php if ($pesan !== ""): ?>
    <div style="padding:12px; margin-bottom:18px;
                background:#dcfce7; color:#166534;
                border-radius:8px;">
        <?= e($pesan) ?>
    </div>
<?php endif; ?>

<?php if (in_array($berhasil, ["tambah", "edit"], true)): ?>
    <div style="padding:12px; margin-bottom:18px;
                background:#dcfce7; color:#166534;
                border-radius:8px;">
        <?= $berhasil === "tambah"
            ? "Laporan barang berhasil ditambahkan."
            : "Laporan barang berhasil diperbarui." ?>
    </div>
<?php endif; ?>

        <div class="toolbar">

            <h2>
                <?= $lang === "id"
                    ? "Daftar Laporan Barang"
                    : "Item Report List" ?>
            </h2>

            <a href="tambah.php" class="button">
                + <?= $lang === "id"
                    ? "Tambah Laporan"
                    : "Add Report" ?>
            </a>

        </div>

        <form action="barang.php" method="GET" class="search-form">

            <input
                type="text"
                name="cari"
                placeholder="<?= $lang === 'id'
                    ? 'Cari nama, kategori, atau lokasi...'
                    : 'Search name, category, or location...' ?>"
                value="<?= e($keyword) ?>"
            >

            <button type="submit" class="button">
                <?= $lang === "id" ? "Cari" : "Search" ?>
            </button>

            <a href="barang.php" class="button button-gray">
                <?= $lang === "id" ? "Reset" : "Reset" ?>
            </a>

        </form>

        <br>

        <div class="total">
            <?= $lang === "id"
                ? "Jumlah laporan yang ditampilkan:"
                : "Reports displayed:" ?>
            <?= $jumlah_data ?>
        </div>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= $lang === "id" ? "Nama Barang" : "Item Name" ?></th>
                        <th><?= $lang === "id" ? "Kategori" : "Category" ?></th>
                        <th><?= $lang === "id" ? "Jenis Laporan" : "Report Type" ?></th>
                        <th><?= $lang === "id" ? "Lokasi" : "Location" ?></th>
                        <th><?= $lang === "id" ? "Tanggal Kejadian" : "Incident Date" ?></th>
                        <th><?= $lang === "id" ? "Status" : "Status" ?></th>
                        <th><?= $lang === "id" ? "Aksi" : "Actions" ?></th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($jumlah_data > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td><?= (int) $row["id"] ?></td>

                            <td><?= e($row["nama_barang"]) ?></td>

                            <td><?= e($row["kategori"]) ?></td>

                            <td>
                                <span class="badge <?= $row["jenis_laporan"] === "Hilang" ? "lost" : "found" ?>">
                                    <?php
                                    if ($lang === "en") {
                                        echo $row["jenis_laporan"] === "Hilang"
                                            ? "Lost"
                                            : "Found";
                                    } else {
                                        echo e($row["jenis_laporan"]);
                                    }
                                    ?>
                                </span>
                            </td>

                            <td><?= e($row["lokasi"]) ?></td>

                            <td>
                                <?= e(date(
                                    "d-m-Y",
                                    strtotime($row["tanggal_kejadian"])
                                )) ?>
                            </td>

                            <td>
                                <span class="badge status">
                                    <?php
                                    $status_en = [
                                        "Belum Ditemukan" => "Not Found Yet",
                                        "Belum Diklaim" => "Not Yet Claimed",
                                        "Sudah Dikembalikan" => "Returned"
                                    ];

                                    echo e(
                                        $lang === "en"
                                            ? ($status_en[$row["status"]] ?? $row["status"])
                                            : $row["status"]
                                    );
                                    ?>
                                </span>
                            </td>

                            <td>
                                <div class="actions">
									<a
									class="button button-detail"
										href="detail.php?id=<?= (int) $row["id"] ?>"
>
										<?= $lang === "id" ? "Detail" : "Details" ?>
									</a>

                                    <a
                                        class="button button-edit"
                                        href="edit.php?id=<?= (int) $row["id"] ?>"
                                    >
                                        <?= $lang === "id" ? "Edit" : "Edit" ?>
                                    </a>

                                    <form
                                        action="hapus.php"
                                        method="POST"
                                        onsubmit="return confirm('<?= $lang === "id"
                                            ? "Yakin ingin menghapus laporan ini?"
                                            : "Are you sure you want to delete this report?" ?>');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $row["id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION["csrf_token"]) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="button button-delete"
                                        >
                                            <?= $lang === "id" ? "Hapus" : "Delete" ?>
                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty">
                            <?= $lang === "id"
                                ? "Belum ada data laporan barang."
                                : "There are no item reports yet." ?>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>