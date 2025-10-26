-- UPLOADS TABLE
CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id VARCHAR(10) UNIQUE NOT NULL, -- A unique identifier for the file (e.g., a hash or part of the unique_code)
    ip_address VARCHAR(45) NOT NULL,    -- Stores IPv4 or IPv6 addresses
    original_file_name VARCHAR(255) NOT NULL,
    hashed_file_name VARCHAR(255) UNIQUE NOT NULL, -- The actual name of the file stored in the 'uploads' folder
    unique_code VARCHAR(6) UNIQUE NOT NULL, -- The 6-digit code for download access
    upload_date DATE NOT NULL,          -- Stored as 'YYYY-MM-DD' for easier database operations
    upload_time TIME NOT NULL,          -- Stored as 'HH:MM:SS'
    max_download_count INT DEFAULT 3,   -- Max downloads allowed (default 3 as per requirement)
    current_download_count INT DEFAULT 0, -- To track current downloads directly in this table
    expiration_timestamp DATETIME NOT NULL -- For easy comparison with current time
);

-- DOWNLOADS TABLE
CREATE TABLE downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id VARCHAR(10) NOT NULL, -- Foreign key referencing uploads.file_id
    ip_address VARCHAR(45) NOT NULL,
    download_timestamp DATETIME NOT NULL, -- When the download occurred
    FOREIGN KEY (file_id) REFERENCES uploads(file_id) ON DELETE CASCADE
);