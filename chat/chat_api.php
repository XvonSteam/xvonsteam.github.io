<?php

namespace xvonsteam;

require_once 'functionDB.php'; // PASTIkan jalur ini benar dari lokasi chat_api.php

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$username = $_SESSION['username'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$targetUser = $_POST['targetUser'] ?? $_GET['targetUser'] ?? null;

// Target user hanya diperlukan untuk aksi sendMessage dan getMessages
if (!$targetUser && ($action === 'sendMessage' || $action === 'getMessages')) {
    echo json_encode(['status' => 'error', 'message' => 'Target user is required for chat actions.']);
    exit();
}


if ($action === 'sendMessage' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? null;
    if ($message) {
        try {
            // Buat instance functionDB untuk sendMessage.
            // Parameter email, password, fullname diisi null karena tidak relevan untuk aksi ini.
            $db = new functionDB('sendMessage', $username, null, null, $message, $targetUser, null);
            $result = $db->performAction();
            echo json_encode(['status' => 'success', 'message' => $result]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty.']);
    }
} elseif ($action === 'getMessages' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Buat instance functionDB untuk getMessages.
        // Parameter email, password, message, fullname diisi null karena tidak relevan untuk aksi ini.
        $db = new functionDB('getMessages', $username, null, null, null, $targetUser, null);
        $messages_json = $db->performAction();
        echo $messages_json; // Sudah JSON formatted dari functionDB
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action or request method.']);
}
