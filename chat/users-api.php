<?php

namespace xvonsteam;

require_once 'functionDB.php'; // PASTIkan jalur ini benar dari lokasi users_api.php

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$username = $_SESSION['username']; // Ambil username dari sesi

try {
    // Buat instance functionDB dengan action 'getRegisteredUsers' dan username saat ini.
    // Parameter lain (email, password, message, targetUser, fullname) diisi null karena tidak relevan untuk aksi ini.
    $db = new functionDB('getRegisteredUsers', $username, null, null, null, null, null);
    $users_json = $db->performAction();
    echo $users_json; // Outputkan JSON
} catch (\Exception $e) {
    error_log("Error in users_api.php: " . $e->getMessage()); // Log error ke server
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    exit();
}
