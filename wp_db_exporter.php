#!/usr/bin/php
<?php

// Mass WordPress database exporter. Each folder will have the db exported.
// The database export is named .ht_site_db.sql, so it can't be accidentally accessed from the web
// Usage: wp_db_exporter.php start_folder
// @author Svetoslav Marinov | https://orbisius.com
// @license GPL

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Output buffering settings
ini_set('implicit_flush', 1);
ini_set('output_buffering', 0);
ini_set('zlib.output_compression', 0);

// Disable PHP's output buffering
ob_implicit_flush(1);
$is_linux = stripos(PHP_OS, 'Linux') !== false;

try {
    // Check if we received a folder name as the first parameter
    if (empty($argv[1])) {
        throw new Exception("Please provide a folder path as the first parameter.");
    }

    $folder = $argv[1];

    // Check if the folder exists
    if (!is_dir($folder)) {
        throw new Exception("The folder does not exist: " . $folder);
    }

    $output = [];
    $exit_code = null;
    exec('wp --info', $output, $exit_code);

    if ($exit_code !== 0) {
        throw new Exception("WP-CLI is not installed or not functioning correctly.");
    }

    // Get the real path of the folder
    $folder = realpath($folder);

    echo "Scanning $folder for wp-configs ...\n";

    // Scan the folder for wp-config.php
    $wp_dirs = [];
    $dir_iterator = new AppReadableDirectoryIterator($folder);
    $iterator = new RecursiveIteratorIterator($dir_iterator);

    foreach ($iterator as $dir) {
        if ($dir->getFilename() === 'wp-config.php') {
            if (is_file($dir->getPath() . '/wp-load.php')) {
                $wp_dirs[] = realpath($dir->getPath());
            }
        }
    }

    // Check if wp-config.php is found
    if (empty($wp_dirs)) {
        throw new Exception("No wp-config.php files found in the specified folder: $folder");
    }

    $dir_count = count($wp_dirs);
    echo "Found $dir_count WordPress directories to process.\n";

    // Loop through each found wp-config.php and export the database
    foreach ($wp_dirs as $wp_dir) {
        echo "Processing $wp_dir\n";

        $wp_config_dir = $wp_dir;
        $export_file_name = $wp_config_dir . DIRECTORY_SEPARATOR . '.ht_site_db.sql';

        // Check if the file already exists and rename it if necessary
        if (file_exists($export_file_name)) {
            $new_file_name = $wp_config_dir . DIRECTORY_SEPARATOR . '.ht_site_db-t' . microtime(true) . '.sql';

            if (rename($export_file_name, $new_file_name)) {
                echo "Existing .ht_site_db.sql renamed to " . basename($new_file_name) . "\n";
            } else {
                echo "Failed to rename existing .ht_site_db.sql\n";
            }
        }

        $escaped_export_file_name = escapeshellarg($export_file_name);
        $escaped_wp_config_path = escapeshellarg($wp_config_dir);

        echo "Exporting db to " . basename($escaped_export_file_name) . "\n";
        $command = "wp db export $escaped_export_file_name --skip-plugins --skip-themes --path=$escaped_wp_config_path";

        // If running on Linux and as root, execute as the folder owner
        if ($is_linux && posix_geteuid() === 0) {
            $folder_owner_info = posix_getpwuid(fileowner($wp_config_dir));
            $folder_owner = $folder_owner_info['name'];
            $escaped_folder_owner = escapeshellarg($folder_owner);
            $command = "sudo -H -u $escaped_folder_owner " . $command;
        }

        $output = null;
        $exit_code = null;
        exec($command, $output, $exit_code);

        if (!empty($output)) {
            echo "Exec output: " . implode("\n", $output) . "\n";
        }

        if ($exit_code !== 0) {
            echo "Failed to export the database for $wp_dir. Command: $command\n";
            continue;
        }

        echo "Database exported successfully to $export_file_name\n";
    }
} catch (Exception $e) {
    echo "Error: An error occurred: " . $e->getMessage() . "\n";
    exit(255);
} finally {
    // You can add any cleanup code here if necessary
}


class AppReadableDirectoryIterator extends RecursiveFilterIterator {
    function __construct($path) {
        if (!$path instanceof RecursiveDirectoryIterator) {
            if (! is_readable($path) || ! is_dir($path))
                throw new InvalidArgumentException("$path is not a valid directory or not readable");
            $path = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        }

        parent::__construct($path);
    }

    /**
     * This method quickly checks if we should even
     * process the folder. If we should we check it
     * if it's readable otherwise PHP stops iterating.
     */
    public function accept() {
        $obj = $this->current();
        $scanned_path = $obj->getPath();

        // convert Windows slashes to Linux
        $scanned_path = str_replace('\\', '/', $scanned_path);

        // dot file?
        if (strpos($scanned_path, '/.') !== false) {
            return false;
        }

        if (strpos($scanned_path, '/wp-includes') !== false) {
            return false;
        }

        if (strpos($scanned_path, '/wp-content') !== false) {
            return false;
        }

        if (strpos($scanned_path, '/wp-admin') !== false) {
            return false;
        }

        return $obj->isReadable(); // $this->current()->isDir() &&
    }
}
