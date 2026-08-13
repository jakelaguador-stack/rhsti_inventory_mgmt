<?php
  require_once('includes/load.php');
  page_require_level(2);
  header('Content-Type: application/json; charset=utf-8');

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
  }

  $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
  $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';

  if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product id']);
    exit;
  }

  global $db;
  $escaped = $db->escape($remark);
  $sql = "UPDATE products SET remarks = '" . $escaped . "' WHERE id = " . $product_id . " LIMIT 1";
  $res = $db->query($sql);
  if ($res) {
    echo json_encode(['status' => 'success']);
  } else {
    echo json_encode(['status' => 'error', 'message' => $db->error]);
  }
  exit;
?>