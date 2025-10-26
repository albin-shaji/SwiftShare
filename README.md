SwiftShare

SwiftShare is a secure, minimal, and ephemeral file-sharing web app. It allows a user to upload a file, receive a unique 6-digit code, and share that code with another person to download the file.

The application is designed for quick, temporary transfers, as files are automatically deleted after a set time (5 minutes) or a maximum number of downloads (3).

Features

Simple UI: A clean, responsive, two-panel interface (for uploading and downloading) built with Tailwind CSS.

6-Digit Share Codes: Generates a unique 6-character alphanumeric code for easy sharing.

Ephemeral Storage: Files are automatically marked for deletion based on:

Time Limit: 5 minutes (defined in config.php).

Download Limit: 3 downloads (defined in config.php).

Secure by Design:

Hashed File Names: Original file names are not used for storage, preventing conflicts and enumeration.

Secure Directories: The uploads/ and expired_files/ directories contain .htaccess files that block all direct HTTP access and script execution, forcing files to be served only as downloads via the PHP script.

AJAX Validation: The download code is validated via an AJAX request before the download is initiated.

Automatic Cleanup: A cleanup.php script is included to move expired files from the active uploads/ directory to the expired_files/ directory.

User-Friendly Notifications: Uses SweetAlert2 for clean, modern pop-up alerts.

PWA Ready: Includes a manifest.json file, allowing users to "Add to Home Screen" on compatible devices.

Technology Stack

Backend: PHP

Database: MySQL

Frontend: HTML5, Tailwind CSS, JavaScript (ES6+)

Libraries: SweetAlert2 (for alerts), Bootstrap Icons (for icons)

Server: Apache (assumed, based on .htaccess files)

Installation & Setup

Follow these steps to get SwiftShare running on your own server.

1. Prerequisites

A web server (like Apache) with PHP support.

A MySQL database.

Ensure the Apache mod_rewrite module is enabled.

2. Database Setup

Create a new MySQL database. The default name in the config is swiftshare.

Import the DATABASE.SQL file into your database. This will create the required uploads and downloads tables.

3. Configuration

Open php/config.php in a text editor.

Update the database connection details:

DB_HOST: Your database host (e.g., localhost)

DB_NAME: Your database name (e.g., swiftshare)

DB_USER: Your database username (e.g., root)

DB_PASS: Your database password

Crucial: Update the BASE_URL constant. This must match the public-facing path to your project.

If your project is at http://example.com/swiftshare/, set it to /swiftshare/.

If your project is at the root http://example.com/, set it to /.

4. Permissions

Your web server needs permission to write to two directories:

uploads/

expired_files/

The config.php script will attempt to create these directories if they don't exist, but you may need to set permissions manually (e.g., chmod -R 755 uploads expired_files and chown www-data:www-data uploads expired_files).

5. (Required) Set Up Cron Job for Cleanup

The application relies on php/cleanup.php to move expired files. You must set up a cron job (or a similar scheduled task) to run this script regularly.

Example Crontab Entry (runs every minute):

* * * * * /usr/bin/php /path/to/your/project/swiftshare/php/cleanup.php


(Remember to replace /path/to/your/project/ with the absolute path on your server).

Project Structure

.
├── SWIFTSHARE/
│   ├── expired_files/
│   │   └── .htaccess         # Blocks direct access to expired files
│   ├── js/
│   │   └── main.js           # Handles all frontend AJAX and interactions
│   ├── logo/
│   │   ├── logo-512x512.png
│   │   └── swiftshare_logo.svg
│   ├── php/
│   │   ├── cleanup_log.txt   # Log file for the cleanup cron job
│   │   ├── cleanup.php       # Script to move expired files (run by cron)
│   │   ├── config.php        # Main configuration (DB, paths, limits)
│   │   ├── download.php      # Handles file validation and download
│   │   └── upload.php        # Handles file upload and code generation
│   ├── uploads/
│   │   └── .htaccess         # Blocks direct access to active uploads
│   ├── DATABASE.SQL          # The MySQL database schema
│   ├── index.html            # The main HTML file for the UI
│   └── manifest.json         # PWA manifest file
