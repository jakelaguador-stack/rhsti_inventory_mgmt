<?php
// CLI-safe migration: adds `remarks` column to `sales` if missing.
// Run via: php migrations/run_add_remarks_cli.php
require_once(__DIR__ . '/../includes/config.php');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    echo "Connect failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error . "\n";
    exit(1);
}

// Check if column exists
$res = $mysqli->query("SHOW COLUMNS FROM sales LIKE 'remarks'");
if ($res === false) {
    echo "ERROR inspecting table: " . $mysqli->error . "\n";
    $mysqli->close();
    exit(1);
}
if ($res->num_rows > 0) {
    echo "Column `remarks` already exists in `sales`. Nothing to do.\n";
    $mysqli->close();
    exit(0);
}

$alter = "ALTER TABLE sales ADD COLUMN remarks TEXT DEFAULT NULL";
if ($mysqli->query($alter) === TRUE) {
    echo "SUCCESS: Added column `remarks` to table `sales`.\n";
    $mysqli->close();
    exit(0);
} else {
    echo "FAILED to add column: " . $mysqli->error . "\n";
    $mysqli->close();
    exit(1);
}
