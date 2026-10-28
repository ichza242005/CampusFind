<?php
session_start();

require_once "config.php";

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Ambil dan bersihkan input
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Validasi input
    if ($username === "" || $password === "") {

        $error = "Username dan password wajib diisi.";

    } else {

        // Cari akun berdasarkan username
        $stmt = $conn->prepare(
            "SELECT id, username, password, nama_lengkap
             FROM users
             WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verifikasi password
        if ($user && password_verify(
            $password,
            $user["password"]
        )) {

            // Buat ID sesi baru setelah login
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["nama_lengkap"] = $user["nama_lengkap"];

            // Atur bahasa default
            if (!isset($_SESSION["bahasa"])) {
                $_SESSION["bahasa"] = "id";
            }

            header("Location: index.php");
            exit;

        } else {

            $error = "Username atau password salah.";

        }

        $stmt->close();
    }
}

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | CampusFind</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eaf2ff, #f8faff);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            padding: 38px;
            border-radius: 18px;
            box-shadow: 0 12px 40px rgba(35, 67, 120, 0.12);
        }

        .logo {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            background: #2563eb;
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
        }

        h1 {
            text-align: center;
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            font-size: 14px;
            margin-bottom: 20px;
            outline: none;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        button {
            width: 100%;
            padding: 13px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 25px;
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="logo">CF</div>

    <h1>CampusFind</h1>

    <p class="subtitle">
        Sistem Informasi Pengelolaan<br>
        Barang Hilang dan Temuan di Kampus
    </p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <label for="username">Username</label>

        <input
            type="text"
            id="username"
            name="username"
            placeholder="Masukkan username"
            maxlength="50"
            autocomplete="username"
            required
        >

        <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
        >

        <button type="submit">Login</button>

    </form>

    <div class="footer">
        &copy; <?= date("Y") ?> CampusFind
    </div>

</div>

</body>
</html>