<?php
// Cek apakah file fisik ada
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $fullPath = __DIR__ . $path;

    // Jika file ada, langsung tampilkan
    if (is_file($fullPath)) {
        return false;
    }

    // Coba tambahkan .php jika file tidak ditemukan
    if (is_file($fullPath . '.php')) {
        $_SERVER["SCRIPT_NAME"] = $path . '.php';
        $_SERVER["SCRIPT_FILENAME"] = $fullPath . '.php';
        include $fullPath . '.php';
        exit;
    }
}

// Fallback ke index.php jika tidak ada file ditemukan
require 'index.php';
