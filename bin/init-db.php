<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Acme\Database\DatabaseInitializer;

$databasePath = __DIR__ . '/../database/cart.sqlite';

// Ensure the database directory exists
$databaseDir = dirname($databasePath);
if (!is_dir($databaseDir)) {
    mkdir($databaseDir, 0777, true);
}

try {
    $initializer = new DatabaseInitializer($databasePath);
    $initializer->initialize();
    echo "Database initialized successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
