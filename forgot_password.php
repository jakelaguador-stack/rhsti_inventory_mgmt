<?php
  $page_title = 'Forgot Password';
  require_once('includes/load.php');
  if($session->isUserLoggedIn(true)) { redirect('home.php', false); }

  $message = '';
  if(isset($_POST['reset'])){
    $username = remove_junk($db->escape($_POST['username']));
    if(empty($username)){
      $session->msg('d','Please enter your username.');
      redirect('forgot_password.php', false);
    }
    // find user by username
    $sql = "SELECT * FROM users WHERE username = '".$db->escape($username)."' LIMIT 1";
    $users = find_by_sql($sql);
    if(empty($users)){
      $session->msg('d','No user found with that username.');
      redirect('forgot_password.php', false);
    }
    $user = $users[0];
    // generate temporary password
    $temp = randString(8);
    $hashed = sha1($temp);
    $update_sql = "UPDATE users SET password = '".$db->escape($hashed)."' WHERE id = '".(int)$user['id']."' LIMIT 1";
    if($db->query($update_sql)){
      $message = "A temporary password has been set for <strong>".htmlspecialchars($user['username'])."</strong>. <br>Use <strong>".htmlspecialchars($temp)."</strong> to login, then change your password immediately.";
    } else {
      $session->msg('d','Failed to reset password.');
      redirect('forgot_password.php', false);
    }
  }
?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-4 col-md-offset-4">
    <div class="panel panel-default" style="margin-top:40px;">
      <div class="panel-heading text-center">
        <strong>Reset Password</strong>
      </div>
      <div class="panel-body">
        <?php echo display_msg($msg); ?>
        <?php if($message !== ''): ?>
          <div class="alert alert-success"><?php echo $message; ?></div>
          <p><a href="index.php">Back to login</a></p>
        <?php else: ?>
          <form method="post" action="forgot_password.php">
            <div class="form-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
              <button type="submit" name="reset" class="btn btn-primary btn-block">Reset Password</button>
            </div>
            <div class="text-center"><a href="index.php">Back to login</a></div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
