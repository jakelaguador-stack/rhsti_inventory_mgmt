<?php
  // Inline remark updater for sales
  require_once('includes/load.php');
  page_require_level(2);
  header('Content-Type: application/json; charset=utf-8');

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
  }

  $sale_id = isset($_POST['sale_id']) ? (int)$_POST['sale_id'] : 0;
  $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';

  if ($sale_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid sale id']);
    exit;
  }

  // Escape remark for DB
  $escaped = $db->escape($remark);
  $sql = "UPDATE sales SET remarks = '" . $escaped . "' WHERE id = " . $sale_id . " LIMIT 1";
  $res = $db->query($sql);
  if ($res) {
    echo json_encode(['status' => 'success']);
  } else {
    echo json_encode(['status' => 'error', 'message' => $db->error]);
  }
  exit;

?>