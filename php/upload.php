<?php
// php/upload.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $file = $_FILES['fileToUpload'];

    // Basic file validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse('error', 'File upload error: ' . $file['error']);
    }

    if ($file['size'] == 0) {
        sendJsonResponse('error', 'Uploaded file is empty.');
    }

    $originalFileName = basename($file['name']);
    $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);

    // Generate unique code (retry if exists)
    $uniqueCode = '';
    $isCodeUnique = false;
    do {
        $uniqueCode = generateUniqueCode();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM uploads WHERE unique_code = ?");
        $stmt->execute([$uniqueCode]);
        if ($stmt->fetchColumn() == 0) {
            $isCodeUnique = true;
        }
    } while (!$isCodeUnique);

    // Generate unique hashed file name
    $hashedFileName = md5(uniqid(rand(), true)) . '.' . $fileExtension;
    $targetFilePath = UPLOAD_DIR . $hashedFileName;

    // Move the uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        sendJsonResponse('error', 'Failed to move uploaded file.');
    }

    // Get client IP address
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    // Calculate upload and expiration times
    $uploadDateTime = new DateTime(); // Current time in IST (set in config.php)
    $uploadDate = $uploadDateTime->format('Y-m-d');
    $uploadTime = $uploadDateTime->format('H:i:s');
    $expirationDateTime = (new DateTime())->modify('+' . FILE_EXPIRATION_MINUTES . ' minutes'); // Add 5 minutes
    $expirationTimestamp = $expirationDateTime->format('Y-m-d H:i:s');

    // Insert into database
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO uploads (file_id, ip_address, original_file_name, hashed_file_name, unique_code, upload_date, upload_time, max_download_count, current_download_count, expiration_timestamp)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        // Generate a simple file_id (e.g., first 10 chars of hashed_file_name)
        $fileId = substr($hashedFileName, 0, 10);

        $stmt->execute([
            $fileId,
            $ipAddress,
            $originalFileName,
            $hashedFileName,
            $uniqueCode,
            $uploadDate,
            $uploadTime,
            MAX_DOWNLOAD_COUNT,
            0, // Initial current_download_count
            $expirationTimestamp
        ]);

        sendJsonResponse('success', 'File uploaded successfully!', ['unique_code' => $uniqueCode]);

    } catch (PDOException $e) {
        // If database insert fails, remove the uploaded file to prevent orphans
        if (file_exists($targetFilePath)) {
            unlink($targetFilePath);
        }
        sendJsonResponse('error', 'Database error: ' . $e->getMessage());
    }
} else {
    sendJsonResponse('error', 'Invalid request method or no file uploaded.');
}
?>