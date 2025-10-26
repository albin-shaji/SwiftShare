<?php
// php/download.php
require_once 'config.php';

if (isset($_GET['code'])) {
    $uniqueCode = trim($_GET['code']);
    $action = $_GET['action'] ?? ''; // NEW: Check for 'action' parameter

    if (empty($uniqueCode) || strlen($uniqueCode) !== 6) {
        sendJsonResponse('error', 'Invalid share code format.');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM uploads WHERE unique_code = ?");
        $stmt->execute([$uniqueCode]);
        $fileData = $stmt->fetch();

        if (!$fileData) {
            sendJsonResponse('error', 'File not found! Try again with a valid code.');
        }

        $hashedFileName = $fileData['hashed_file_name'];
        $originalFileName = $fileData['original_file_name'];
        $filePath = UPLOAD_DIR . $hashedFileName;

        // --- Common Checks (Applicable to both validation and actual download) ---
        // Check if file physically exists in uploads folder
        if (!file_exists($filePath)) {
            // This might happen if cleanup.php ran or file was manually moved/deleted
            sendJsonResponse('error', 'The file you are trying to download is no longer available! Ask the sender to re-upload.');
        }

        // Check expiration time
        $currentTime = new DateTime(); // Current time in IST
        $expirationTime = new DateTime($fileData['expiration_timestamp']);
        if ($currentTime > $expirationTime) {
            // File expired by time
            // Move file to expired_files if it's still in uploads, then send error
            if (file_exists($filePath)) {
                rename($filePath, EXPIRED_DIR . $hashedFileName);
            }
            sendJsonResponse('error', 'Sorry! File has been expired.');
        }

        // Check download count
        if ($fileData['current_download_count'] >= $fileData['max_download_count']) {
            // File expired by download count
            // Move file to expired_files if it's still in uploads, then send error
            if (file_exists($filePath)) {
                rename($filePath, EXPIRED_DIR . $hashedFileName);
            }
            sendJsonResponse('error', 'Download limit reached for this file.');
        }
        // --- End Common Checks ---

        // NEW LOGIC: If the 'action' is 'validate', we only return success. No increment, no file serving.
        if ($action === 'validate') {
            sendJsonResponse('success', 'Code is valid and file is available.');
        }

        // OLD LOGIC (below): ONLY PROCEED WITH DOWNLOAD LOGIC IF 'action' IS NOT 'validate'
        // This is the crucial part that prevents double increment
        // 1. Update current_download_count in uploads table
        $newDownloadCount = $fileData['current_download_count'] + 1;
        $updateStmt = $pdo->prepare("UPDATE uploads SET current_download_count = ? WHERE unique_code = ?");
        $updateStmt->execute([$newDownloadCount, $uniqueCode]);

        // 2. Log download in downloads table
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $downloadTimestamp = $currentTime->format('Y-m-d H:i:s');
        $logStmt = $pdo->prepare(
            "INSERT INTO downloads (file_id, ip_address, download_timestamp)
             VALUES (?, ?, ?)"
        );
        $logStmt->execute([
            $fileData['file_id'],
            $ipAddress,
            $downloadTimestamp
        ]);

        // 3. Serve the file
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $originalFileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        ob_clean();
        flush();
        readfile($filePath);

        // 4. IMPORTANT: After serving, check if download limit is now reached and move the file
        if ($newDownloadCount >= $fileData['max_download_count']) {
            if (file_exists($filePath)) { // Check again in case cleanup ran concurrently
                rename($filePath, EXPIRED_DIR . $hashedFileName);
            }
        }
        exit; // Terminate script after file is served

    } catch (PDOException $e) {
        // Log the error for debugging
        error_log("Download Error: " . $e->getMessage());
        // Send JSON error response even for direct download requests if a DB error occurs before headers are sent
        sendJsonResponse('error', 'Database error during download: ' . $e->getMessage());
    }
} else {
    sendJsonResponse('error', 'No share code provided.');
}
?>