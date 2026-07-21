<?php
// Safe migration: add serial_number and receipt_number to products if they don't exist.
require_once __DIR__ . '/../includes/load.php';

// Ensure helper exists
if (!function_exists('columnExists')) {
  if (file_exists(__DIR__ . '/../includes/sql.php')) {
    require_once __DIR__ . '/../includes/sql.php';
  }
}

$added = [];
$errors = [];

if (!columnExists('products', 'serial_number')) {
  $sql = "ALTER TABLE products ADD COLUMN serial_number VARCHAR(100) DEFAULT '' AFTER media_id";
  if ($db->query($sql)) {
    $added[] = 'serial_number';
  } else {
    $errors[] = 'serial_number: ' . $db->error;
  }
} else {
  echo "Column serial_number already exists\n";
}

if (!columnExists('products', 'receipt_number')) {
  $sql = "ALTER TABLE products ADD COLUMN receipt_number VARCHAR(100) DEFAULT '' AFTER serial_number";
  if ($db->query($sql)) {
    $added[] = 'receipt_number';
  } else {
    $errors[] = 'receipt_number: ' . $db->error;
  }
} else {
  echo "Column receipt_number already exists\n";
}

if (!empty($added)) {
  echo "Added columns: " . implode(', ', $added) . "\n";
}
if (!empty($errors)) {
  echo "Errors:\n" . implode("\n", $errors) . "\n";
}

// Print final table structure for verification (column names only)
$res = $db->query("SHOW COLUMNS FROM products");
if ($res) {
  $cols = [];
  while ($row = $db->fetch_assoc($res)) {
    $cols[] = $row['Field'];
  }
  echo "Products columns: " . implode(', ', $cols) . "\n";
} else {
  echo "Could not read products columns: " . $db->error . "\n";
}

echo "Migration completed.\n";

?>
