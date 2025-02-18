<?php
require_once 'config.php';

// Only allow this script to run from the command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line');
}

// Update the first user to be an admin
try {
    $db->exec("UPDATE users SET role = 'admin' WHERE id = 1");
    echo "Successfully made user ID 1 an admin\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
