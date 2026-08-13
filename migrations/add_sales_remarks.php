<?php
// Safe migration: adds `remarks` TEXT column to `sales` if missing.
// Run locally only: http://localhost/inventory_system/migrations/add_sales_remarks.php
require_once(__DIR__ . '/../includes/load.php');
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
  echo "Access restricted. Run locally via localhost only.";
  exit;
}

header('Content-Type: text/plain; charset=utf-8');
$db = $GLOBALS['db'];
$col_check = $db->query("SHOW COLUMNS FROM sales LIKE 'remarks'");
if ($col_check === false) {
  echo "ERROR: Could not inspect sales table: " . $db->error . "\n";
  exit;
}
if ($col_check->num_rows > 0) {
  echo "Column `remarks` already exists in `sales`. Nothing to do.\n";
  exit;
}

$alter = "ALTER TABLE sales ADD COLUMN remarks TEXT DEFAULT NULL";
if ($db->query($alter)) {
  echo "SUCCESS: Added column `remarks` to table `sales`.\n";
} else {
  echo "FAILED to add column: " . $db->error . "\n";
}
