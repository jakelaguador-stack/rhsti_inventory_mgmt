<?php
  require_once('includes/load.php');
  if (!$session->isUserLoggedIn(true)) { redirect('index.php', false); }
  $session->msg("d", "Edit item history is no longer available. Manage inventory usage from the Products page.");
  redirect('product.php', false);
  exit;
?>
