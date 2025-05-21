<?php

namespace xvonsteam;

// Pastikan jalur ke functionDB.php benar dari lokasi register.php
// Jika register.php di root dan functionDB.php juga di root:
require_once 'functionDB.php';

session_start();

$notification = []; // Untuk pesan SweetAlert2

// Cek jika form disubmit dengan method POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? null; // Ambil nama lengkap
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    // Pastikan semua kolom terisi, termasuk fullname
    if ($fullname && $username && $email && $password) {
        try {
            // Inisialisasi functionDB dengan action 'register' dan semua data yang diperlukan
            // Urutan parameter di constructor functionDB:
            // __construct($action, $username, $email, $password, $message, $targetUser, $fullname)
            $db = new functionDB('register', $username, $email, $password, null, null, $fullname);
            $result = $db->performAction();

            if (strpos($result, "successfully") !== false) {
                // Pendaftaran berhasil
                $notification = [
                    'icon' => 'success',
                    'title' => 'Pendaftaran Berhasil!',
                    'text' => $result,
                    'redirect' => 'login.php' // Redirect ke halaman login setelah daftar
                ];
            } else {
                // Pendaftaran gagal
                $notification = [
                    'icon' => 'error',
                    'title' => 'Pendaftaran Gagal!',
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
        // Jika ada kolom yang kosong
        $notification = [
            'icon' => 'warning',
            'title' => 'Input Tidak Lengkap!',
            'text' => "Nama lengkap, username, email, dan password diperlukan."
        ];
    }
    // Simpan notifikasi ke sesi agar bisa ditampilkan setelah reload (jika ada redirect)
    $_SESSION['notification'] = $notification;
    // Lakukan redirect ke halaman register.php itu sendiri untuk menampilkan SweetAlert
    header('Location: register.php');
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar - XvonSteam Framework</title>

    <link href="https://unpkg.com/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet" />

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
    <link href="assets/icon/icon.jpg" rel="icon" />
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
            /* Tambahkan padding kembali untuk tampilan yang lebih baik */
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
            <img src="assets/icon/icon.jpg" class="img-fluid rounded-circle" width="60" alt="Logo" />
            <h3 class="mt-2">Daftar</h3>
        </div>
        <form action="" method="POST">
            <div class="mb-3"> <label for="fullname" class="form-label">Nama Lengkap</label>
                <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Masukkan nama lengkap" required />
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Buat username" required />
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Masukkan email" required />
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Kata Sandi</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Buat password" required />
            </div>

            <div class="d-grid mt-3 mb-2">
                <button type="submit" class="btn btn-primary">Daftar</button>
            </div>
        </form>

        <!-- <div class="d-grid mb-2">
            <a href="google-login.php" class="btn google-btn">
                <img src="assets/icon/google.png" alt="Google" /> Daftar dengan Google
            </a>
        </div> -->

        <a href="login.php" class="register-link text-muted">Sudah punya akun? Masuk di sini</a>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        <?php if (!empty($current_notification)): ?>
            const notification = <?php echo json_encode($current_notification); ?>;
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
        <?php endif; ?>
    </script>

</body>

</html>