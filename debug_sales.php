<?php
// Temporary debug page for sales/products. Restricted to localhost.
require_once('includes/load.php');
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
  echo "Access restricted. Run locally or via localhost only.";
  exit;
}
echo "<pre style=\"white-space:pre-wrap;\">";
// sales count
$res = $db->query("SELECT COUNT(*) AS c FROM sales");
if ($res) {
  $r = $res->fetch_assoc();
  echo "sales.count: " . ($r['c'] ?? '0') . "\n\n";
}
// distinct product ids used
$res = $db->query("SELECT COUNT(DISTINCT product_id) AS c FROM sales");
if ($res) {
  $r = $res->fetch_assoc();
  echo "sales.distinct_product_ids: " . ($r['c'] ?? '0') . "\n\n";
}
// sample sales
echo "sales sample (latest 50):\n";
$res = $db->query("SELECT id, product_id, qty, date FROM sales ORDER BY date DESC LIMIT 50");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "\n";
  }
} else {
  echo "Query failed: " . $db->error . "\n";
}

echo "\nproducts referenced by sales (first 100):\n";
$res = $db->query("SELECT DISTINCT p.id, p.name, p.categorie_id, p.buy_price FROM products p JOIN sales s ON p.id = s.product_id LIMIT 100");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "\n";
  }
}

echo "\nAll categories (first 100):\n";
$res = $db->query("SELECT id, name FROM categories LIMIT 100");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "\n";
  }
}

echo "\nGenerated SQL used_items.php uses (for reference):\n";
echo htmlspecialchars(
  "SELECT p.id AS product_id, p.name, s.id AS sale_id, ABS(s.qty) AS used, p.buy_price, p.unit, s.date, c.name AS categorie, c.id AS categorie_id FROM sales s LEFT JOIN products p ON p.id = s.product_id LEFT JOIN categories c ON c.id = p.categorie_id WHERE s.qty <> 0 ORDER BY p.id ASC, s.date ASC"
);

echo "</pre>";
