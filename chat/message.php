<?php

namespace xvonsteam;

require_once 'functionDB.php'; // Hanya butuh functionDB.php

session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('location:login.php');
    exit();
}

$username = $_SESSION['username']; // Pengguna yang sedang login
$targetUser = $_GET['targetUser'] ?? null; // Pengguna yang akan diajak chat

// Jika tidak ada targetUser, redirect kembali ke daftar kontak
if (!$targetUser) {
    header('location:index.php');
    exit();
}

// --- START: Penanganan notifikasi SweetAlert dari sesi ---
$current_notification = [];
if (isset($_SESSION['notification'])) {
    $current_notification = $_SESSION['notification'];
    unset($_SESSION['notification']); // Hapus setelah diambil
}
// --- END: Penanganan notifikasi SweetAlert dari sesi ---

// Ambil pesan awal saat halaman dimuat (PHP side)
$initialMessages = [];
try {
    // Inisialisasi functionDB untuk getMessages.
    // Parameter email, password, message, fullname diisi null karena tidak relevan.
    $db = new functionDB('getMessages', $username, null, null, null, $targetUser, null);
    $messages_json = $db->performAction();
    $decoded_messages = json_decode($messages_json, true);

    if (is_array($decoded_messages)) {
        $initialMessages = $decoded_messages;
    } else {
        error_log("Error decoding initial messages JSON in message.php: " . $messages_json);
        $current_notification = [
            'icon' => 'error',
            'title' => 'Error!',
            'text' => 'Gagal memuat pesan awal: Data tidak valid.'
        ];
    }
} catch (\Exception $e) {
    error_log("Exception fetching initial messages in message.php: " . $e->getMessage());
    $current_notification = [
        'icon' => 'error',
        'title' => 'Error!',
        'text' => 'Terjadi kesalahan saat memuat pesan awal: ' . $e->getMessage()
    ];
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat dengan <?= htmlspecialchars($targetUser) ?></title>

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

        .chat-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            height: 85vh;
            /* Set a height for the chat area */
        }

        .chat-header {
            text-align: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 0.5rem;
        }

        .chat-messages {
            flex-grow: 1;
            /* Allow messages to take up available space */
            border: 1px solid #e0e0e0;
            border-radius: 0.5rem;
            padding: 1rem;
            overflow-y: auto;
            /* Enable scrolling for messages */
            margin-bottom: 1rem;
            background-color: #fcfcfc;
        }

        .message-item {
            margin-bottom: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            word-wrap: break-word;
        }

        .message-item.sent {
            background-color: #e0f2f7;
            /* Light blue for sent messages */
            text-align: right;
            margin-left: auto;
            /* Push to the right */
            max-width: 90%;
            /* Limit width */
        }

        .message-item.received {
            background-color: #f0f0f0;
            /* Light gray for received messages */
            text-align: left;
            margin-right: auto;
            /* Push to the left */
            max-width: 90%;
            /* Limit width */
        }

        .message-item strong {
            display: block;
            /* Make sender name appear on its own line */
            font-size: 0.9em;
            color: #0056b3;
            margin-bottom: 0.25rem;
        }

        .message-item.sent strong {
            color: #0056b3;
        }

        .message-item .timestamp {
            font-size: 0.7em;
            color: #888;
            margin-top: 0.25rem;
            display: block;
        }

        .message-form {
            display: flex;
            gap: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
        }

        .message-form input[type="text"] {
            flex-grow: 1;
        }

        .back-button {
            margin-top: 1rem;
            display: block;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <div class="chat-header">
            <h3 id="chattingWith">Chat dengan <?= htmlspecialchars($targetUser) ?></h3>
        </div>

        <div class="chat-messages" id="chatMessages">
        </div>

        <form class="message-form" id="chatForm">
            <input type="text" id="messageInput" class="form-control" placeholder="Tulis pesan Anda..." required>
            <button type="submit" class="btn btn-primary" id="sendMessageBtn">Kirim</button>
        </form>

        <a href="index.php" class="btn btn-secondary back-button">Kembali ke Kontak</a>
    </div>

    <script src="https://unpkg.com/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
        const loggedInUser = "<?= htmlspecialchars($username) ?>";
        const targetUser = "<?= htmlspecialchars($targetUser) ?>";
        const chatMessagesDiv = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const chatForm = document.getElementById('chatForm');

        let fetchMessagesInterval = null;
        let hasShownFetchError = false;

        // Fungsi helper untuk menghindari XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        // Fungsi untuk merender pesan ke UI
        function renderMessages(messages) {
            chatMessagesDiv.innerHTML = ''; // Hapus pesan yang sudah ada
            if (messages && messages.length > 0) {
                messages.forEach(msg => {
                    const messageElement = document.createElement('div');
                    messageElement.classList.add('message-item');
                    if (msg.sender === loggedInUser) {
                        messageElement.classList.add('sent');
                        messageElement.innerHTML = `
                            <strong>Anda:</strong> ${escapeHtml(msg.message)}
                            <span class="timestamp">${escapeHtml(msg.timestamp)}</span>
                        `;
                    } else {
                        messageElement.classList.add('received');
                        messageElement.innerHTML = `
                            <strong>${escapeHtml(msg.sender)}:</strong> ${escapeHtml(msg.message)}
                            <span class="timestamp">${escapeHtml(msg.timestamp)}</span>
                        `;
                    }
                    chatMessagesDiv.appendChild(messageElement);
                });
                chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight; // Gulir ke bawah
            } else {
                chatMessagesDiv.innerHTML = '<div class="text-center text-muted">Belum ada pesan. Mulai percakapan!</div>';
            }
        }

        // Fungsi untuk mengambil pesan dari chat_api.php (menggunakan AJAX)
        function fetchMessages() {
            fetch(`chat_api.php?action=getMessages&targetUser=${encodeURIComponent(targetUser)}`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().catch(() => {
                            throw new Error(`HTTP error! status: ${response.status} - Could not parse error response.`);
                        }).then(errorData => {
                            throw new Error(`HTTP error! status: ${response.status} - ${errorData.message || 'Unknown error'}`);
                        });
                    }
                    return response.json();
                })
                .then(messages => {
                    hasShownFetchError = false; // Reset flag error jika berhasil
                    renderMessages(messages); // Render pesan yang diterima
                })
                .catch(error => {
                    console.error('Error fetching messages:', error);
                    chatMessagesDiv.innerHTML = '<div class="text-center text-danger">Gagal memuat pesan.</div>';
                    if (!hasShownFetchError) {
                        Swal.fire('Error', error.message || 'Gagal memuat pesan chat. Coba refresh halaman.', 'error');
                        hasShownFetchError = true;
                    }
                });
        }

        // Event listener untuk mengirim pesan
        chatForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah form submit secara default
            const message = messageInput.value.trim();

            if (message) {
                const formData = new FormData();
                formData.append('action', 'sendMessage');
                formData.append('message', message);
                formData.append('targetUser', targetUser);

                fetch('chat_api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().catch(() => {
                                throw new Error(`HTTP error! status: ${response.status} - Could not parse error response.`);
                            }).then(errorData => {
                                throw new Error(`HTTP error! status: ${response.status} - ${errorData.message || 'Unknown error'}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            messageInput.value = ''; // Kosongkan input
                            fetchMessages(); // Ambil pesan terbaru setelah mengirim
                        } else {
                            Swal.fire('Gagal', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error sending message:', error);
                        Swal.fire('Error', error.message || 'Gagal mengirim pesan.', 'error');
                    });
            } else {
                Swal.fire('Peringatan', 'Pesan tidak boleh kosong!', 'warning');
            }
        });

        // Tampilkan pesan awal yang dimuat oleh PHP
        document.addEventListener('DOMContentLoaded', () => {
            const initialMessagesData = <?= json_encode($initialMessages) ?>;
            renderMessages(initialMessagesData);

            // Mulai polling untuk pesan baru
            fetchMessagesInterval = setInterval(fetchMessages, 3000);
        });

        // Bersihkan interval saat meninggalkan halaman
        window.addEventListener('beforeunload', () => {
            if (fetchMessagesInterval) {
                clearInterval(fetchMessagesInterval);
            }
        });

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