<?php
// php/cleanup.php
require_once 'config.php';

// Log for debugging cleanup script runs (optional)
file_put_contents(__DIR__ . '/cleanup_log.txt', date('Y-m-d H:i:s') . " - Cleanup script started.\n", FILE_APPEND);


try {
    // 1. Find files expired by time
    $stmt = $pdo->prepare(
        "SELECT hashed_file_name, original_file_name, unique_code
         FROM uploads
         WHERE expiration_timestamp <= NOW()
           AND hashed_file_name NOT IN (SELECT hashed_file_name FROM uploads WHERE current_download_count >= max_download_count)"
           // The second condition prevents moving files already moved by download limit.
    );
    $stmt->execute();
    $timeExpiredFiles = $stmt->fetchAll();

    foreach ($timeExpiredFiles as $file) {
        $filePath = UPLOAD_DIR . $file['hashed_file_name'];
        if (file_exists($filePath)) {
            rename($filePath, EXPIRED_DIR . $file['hashed_file_name']);
            file_put_contents(__DIR__ . '/cleanup_log.txt', date('Y-m-d H:i:s') . " - Moved time-expired file: " . $file['original_file_name'] . " (Code: " . $file['unique_code'] . ")\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/cleanup_log.txt', date('Y-m-d H:i:s') . " - Time-expired file not found (already moved/deleted?): " . $file['original_file_name'] . " (Code: " . $file['unique_code'] . ")\n", FILE_APPEND);
        }
    }

    // 2. Find files expired by download count (if not already moved by download.php's immediate check)
    $stmt = $pdo->prepare(
        "SELECT hashed_file_name, original_file_name, unique_code
         FROM uploads
         WHERE current_download_count >= max_download_count"
    );
    $stmt->execute();
    $downloadLimitExpiredFiles = $stmt->fetchAll();

    foreach ($downloadLimitExpiredFiles as $file) {
        $filePath = UPLOAD_DIR . $file['hashed_file_name'];
        if (file_exists($filePath)) {
            rename($filePath, EXPIRED_DIR . $file['hashed_file_name']);
            file_put_contents(__DIR__ . '/cleanup_log.txt', date('Y-m-d H:i:s') . " - Moved download-limit-expired file: " . $file['original_file_name'] . " (Code: " . $file['unique_code'] . ")\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/cleanup_log.txt', date('Y-m-d H:i:s') . " - Download-limit-expired file not found (already moved/deleted?): " . $file['original_file_name'] . " (Code: " . $file['unique_code'] . ")\n", FILE_APPEND);
        }
    }

} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/cleanup_log.txt', date('Y-m-d H:i:s') . " - Database error during cleanup: " . $e->getMessage() . "\n", FILE_APPEND);
}

file_put_contents(__DIR__ . '/cleanup_log.txt', date('Y-m-d H:i:s') . " - Cleanup script finished.\n", FILE_APPEND);
?>