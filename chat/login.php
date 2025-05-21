<?php

namespace xvonsteam;

require_once 'functionDB.php'; // Sesuaikan path jika functionDB.php berada di direktori lain

session_start(); // Mulai sesi untuk menyimpan status login

$notification = []; // Array untuk menyimpan pesan notifikasi SweetAlert2

// --- START: Penyesuaian untuk menangani pesan dari GET (misal dari register.php) ---
if (isset($_GET['message'])) {
    $msg = htmlspecialchars_decode($_GET['message']); // Decode pesan dari URL
    $msgLower = strtolower($msg);

    $icon = 'info'; // Default icon
    if (strpos($msgLower, 'berhasil') !== false || strpos($msgLower, 'successfully') !== false) {
        $icon = 'success';
    } elseif (strpos($msgLower, 'gagal') !== false || strpos($msgLower, 'failed') !== false || strpos($msgLower, 'error') !== false || strpos($msgLower, 'tidak boleh kosong') !== false) {
        $icon = 'error';
    } elseif (strpos($msgLower, 'diperlukan') !== false || strpos($msgLower, 'invalid') !== false) {
        $icon = 'warning';
    }

    // Simpan pesan GET ke dalam format notifikasi sesi
    $_SESSION['notification'] = [
        'icon' => $icon,
        'title' => 'Pesan Sistem', // Judul generik untuk pesan dari GET
        'text' => $msg,
        'redirect' => null // Tidak ada redirect otomatis untuk pesan dari GET
    ];
    // Hapus parameter 'message' dari URL agar tidak muncul lagi saat refresh
    header('Location: login.php');
    exit();
}
// --- END: Penyesuaian untuk menangani pesan dari GET ---


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($username && $password) {
        try {
            // Inisialisasi functionDB untuk login.
            // Parameter fullname, message, targetUser tidak relevan di sini, jadi bisa null.
            $db = new functionDB('login', $username, null, $password, null, null, null);
            $result = $db->performAction();

            if (strpos($result, "successfully") !== false) {
                // Login berhasil
                $_SESSION['username'] = $username;
                $notification = [
                    'icon' => 'success',
                    'title' => 'Login Berhasil!',
                    'text' => $result,
                    'redirect' => 'index.php' // URL untuk redirect setelah SweetAlert
                ];
            } else {
                // Login gagal
                $notification = [
                    'icon' => 'error',
                    'title' => 'Login Gagal!',
                    'text' => $result
                ];
            }
        } catch (\InvalidArgumentException $e) {
            $notification = [
                'icon' => 'error',
                'title' => 'Error!',
                'text' => "Error: " . $e->getMessage()
            ];
        } catch (\Exception $e) {
            $notification = [
                'icon' => 'error',
                'title' => 'Terjadi Kesalahan!',
                'text' => "Terjadi kesalahan tak terduga: " . $e->getMessage()
            ];
        }
    } else {
        $notification = [
            'icon' => 'warning',
            'title' => 'Input Tidak Lengkap!',
            'text' => "Username dan password diperlukan."
        ];
    }
    // Simpan notifikasi ke sesi agar bisa ditampilkan setelah reload (jika ada redirect)
    $_SESSION['notification'] = $notification;
    // Redirect ke halaman login.php itu sendiri untuk menampilkan SweetAlert
    header('Location: login.php');
    exit();
}

// Tangani notifikasi SweetAlert dari sesi jika ada (setelah redirect)
$current_notification = [];
if (isset($_SESSION['notification'])) {
    $current_notification = $_SESSION['notification'];
    unset($_SESSION['notification']); // Hapus setelah diambil
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XvonSteam Framework - Login</title>

    <link href="https://unpkg.com/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="assets/icon/icon.jpg" rel="icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding: 2rem;
            background-color: #f8f9fa;
        }

        .welcome-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .google-btn {
            background: white;
            border: 1px solid #ccc;
            color: #444;
        }

        .google-btn img {
            height: 18px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .form-label {
            font-weight: 600;
        }

        .register-link {
            display: block;
            margin-top: 1rem;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="welcome-container">
        <div class="text-center mb-4">
            <img src="assets/icon/icon.jpg" class="img-fluid rounded-circle" width="60" alt="Logo">
            <h3 class="mt-2">Login</h3>
        </div>

        <form action="login.php" method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">Masuk</button>
            </div>
        </form>

        <!-- <div class="d-grid mb-2">
            <a href="google-login.php" class="btn google-btn">
                <img src="assets/icon/google.png" alt="Google"> Login dengan Google
            </a>
        </div> -->

        <a href="register.php" class="register-link text-muted">Belum punya akun? Daftar di sini</a>
    </div>

    <script src="https://unpkg.com/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <?php if (!empty($current_notification)) : ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const notification = <?= json_encode($current_notification) ?>;
                Swal.fire({
                    icon: notification.icon,
                    title: notification.title,
                    text: notification.text,
                    showConfirmButton: notification.redirect ? false : true,
                    timer: notification.redirect ? 2000 : null
                }).then((result) => {
                    if (notification.redirect) {
                        window.location.href = notification.redirect;
                    }
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>