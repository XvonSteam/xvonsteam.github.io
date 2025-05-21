<?php

namespace xvonsteam;

require_once 'functionDB.php'; // Pastikan jalur ini benar dari lokasi index.php

session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('location:login.php');
    exit();
}

$username = $_SESSION['username']; // Username pengguna yang sedang login

// --- START: Penanganan notifikasi SweetAlert dari sesi ---
$current_notification = [];
if (isset($_SESSION['notification'])) {
    $current_notification = $_SESSION['notification'];
    unset($_SESSION['notification']); // Hapus setelah diambil
}
// --- END: Penanganan notifikasi SweetAlert dari sesi ---

$contactUsers = []; // Variabel untuk menyimpan daftar kontak pengguna
try {
    // Buat instance functionDB untuk mendapatkan daftar kontak pengguna terdaftar.
    // Parameter lain diisi null karena tidak relevan untuk aksi ini.
    $db = new functionDB('getRegisteredUsers', $username, null, null, null, null, null);
    $users_json = $db->performAction(); // Panggil method untuk mendapatkan daftar pengguna dalam format JSON

    // Decode JSON menjadi array PHP
    $decoded_users = json_decode($users_json, true);

    // Pastikan hasil decode adalah array
    if (is_array($decoded_users)) {
        $contactUsers = $decoded_users;
    } else {
        $contactUsers = [];
        error_log("Error decoding users_json in index.php: " . $users_json);
        $current_notification = [
            'icon' => 'error',
            'title' => 'Error!',
            'text' => 'Gagal memuat daftar kontak: Data tidak valid.'
        ];
    }
} catch (\Exception $e) {
    error_log("Exception in index.php (getRegisteredUsers): " . $e->getMessage());
    $current_notification = [
        'icon' => 'error',
        'title' => 'Error!',
        'text' => 'Gagal memuat daftar kontak: ' . $e->getMessage()
    ];
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XvonSteam Framework - Kontak Chat</title>

    <link href="https://unpkg.com/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="assets/icon/icon.jpg" rel="icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding: 2rem;
            background-color: #f8f9fa;
        }

        .main-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            min-height: 70vh;
        }

        .contact-list-section {
            flex-grow: 1;
            padding-top: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* Untuk menempatkan tombol di kanan */
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #eee;
            background-color: #fcfcfc;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .contact-item:last-child {
            border-bottom: none;
        }

        .contact-details-wrapper {
            display: flex;
            align-items: center;
            flex-grow: 1;
            /* Memungkinkan detail mengisi ruang */
        }

        .contact-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 1.2em;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .contact-details {
            text-align: left;
        }

        .contact-details strong {
            display: block;
            font-size: 1.1em;
            color: #333;
        }

        .contact-details span {
            font-size: 0.9em;
            color: #666;
        }

        .message-button-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            /* Jarak antara ikon dan tombol */
        }

        .message-icon {
            color: #28a745;
            /* Warna hijau untuk ikon pesan */
            font-size: 1.2em;
        }

        .logout-btn {
            margin-top: 2rem;
            display: block;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="text-center mb-4">
            <img src="assets/icon/icon.jpg" class="img-fluid rounded-circle mb-3" width="60" alt="Logo">
            <h1>Selamat Datang, <strong><?= htmlspecialchars($username) ?></strong></h1>
            <p class="text-muted">Pilih Kontak untuk Chat</p>
        </div>

        <div class="contact-list-section">
            <?php if (empty($contactUsers)) : ?>
                <div class="alert alert-info text-center" role="alert">
                    Tidak ada kontak lain yang terdaftar. Silakan <a href="register.php">daftar</a> beberapa pengguna.
                </div>
            <?php else : ?>
                <?php foreach ($contactUsers as $user_contact) : ?>
                    <div class="contact-item">
                        <div class="contact-details-wrapper">
                            <div class="contact-avatar">
                                <?php
                                // Ambil 2 huruf pertama dari nama lengkap atau username untuk avatar
                                $avatarText = '';
                                if (isset($user_contact['fullname']) && !empty($user_contact['fullname'])) {
                                    $words = explode(' ', $user_contact['fullname']);
                                    if (count($words) >= 2) {
                                        $avatarText = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                                    } else {
                                        $avatarText = strtoupper(substr($user_contact['fullname'], 0, 2));
                                    }
                                } else if (isset($user_contact['username']) && !empty($user_contact['username'])) {
                                    $avatarText = strtoupper(substr($user_contact['username'], 0, 2));
                                } else {
                                    $avatarText = '??';
                                }
                                echo htmlspecialchars($avatarText);
                                ?>
                            </div>
                            <div class="contact-details">
                                <strong><?= htmlspecialchars($user_contact['fullname'] ?? $user_contact['username']) ?></strong>
                                <span>@<?= htmlspecialchars($user_contact['username']) ?></span>
                            </div>
                        </div>
                        <div class="message-button-group">
                            <?php if ($user_contact['has_messages']) : ?>
                                <i class="fas fa-comment-dots message-icon" title="Ada pesan baru"></i>
                            <?php endif; ?>
                            <a href="message.php?targetUser=<?= urlencode($user_contact['username']) ?>" class="btn btn-primary btn-sm">Kirim Pesan</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="logout.php" class="btn btn-danger logout-btn">Keluar</a>
    </div>

    <script src="https://unpkg.com/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
        // --- START: Penanganan notifikasi SweetAlert dari sesi ---
        <?php if (!empty($current_notification)) : ?>
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
        <?php endif; ?>
        // --- END: Penanganan notifikasi SweetAlert dari sesi ---
    </script>
</body>

</html>