<?php
  $page_title = 'Edit Account';
  require_once('includes/load.php');
  page_require_level(3);
?>
<?php
//update user image
  if(isset($_POST['submit'])) {
  $photo = new Media();
  $user_id = (int)$_POST['user_id'];
  $photo->upload($_FILES['file_upload']);
  if($photo->process_user($user_id)){
    $session->msg('s','photo has been uploaded.');
    redirect('edit_account.php');
    } else{
      $session->msg('d',join($photo->errors));
      redirect('edit_account.php');
    }
  }
?>
<?php
 //update user other info
  if(isset($_POST['update'])){
    $req_fields = array('name','username' );
    validate_fields($req_fields);
    if(empty($errors)){
           $id = (int)$_SESSION['user_id'];
         $name = remove_junk($db->escape($_POST['name']));
     $username = remove_junk($db->escape($_POST['username']));
          $sql = "UPDATE users SET name ='{$name}', username ='{$username}' WHERE id='{$id}'";
    $result = $db->query($sql);
          if($result && $db->affected_rows() === 1){
            $session->msg('s',"Account updated ");
            redirect('edit_account.php', false);
          } else {
            $session->msg('d',' Sorry failed to updated!');
            redirect('edit_account.php', false);
          }
    } else {
      $session->msg("d", $errors);
      redirect('edit_account.php',false);
    }
  }
?>
<?php include_once('layouts/header.php'); ?>

<!-- Custom Styling para sa Profile Image para lalong gumanda -->
<style>
  .profile-img-container {
    width: 120px;
    height: 120px;
    overflow: hidden;
    border-radius: 50%;
    border: 3px solid #e3e6f0;
    margin: 0 auto 15px auto;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  }
  .profile-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .card {
    border: none;
    border-radius: 8px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    margin-bottom: 30px;
  }
  .card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    font-weight: 600;
    color: #4e73df;
  }
</style>

<div class="container-fluid mt-4">
  <div class="row">
    <div class="col-md-12">
      <?php echo display_msg($msg); ?>
    </div>
  </div>

  <div class="row">
    <!-- Left Column: Change Photo -->
    <div class="col-lg-5 col-md-12">
      <div class="card shadow-sm">
        <div class="card-header py-3 d-flex align-items-center">
          <i class="glyphicon glyphicon-camera mr-2" style="margin-right: 8px;"></i>
          <h6 class="m-0 font-weight-bold text-primary">Change Profile Photo</h6>
        </div>
        <div class="card-body text-center py-4">
          <div class="profile-img-container">
            <img src="uploads/users/<?php echo $user['image'];?>" alt="User Avatar">
          </div>
          
          <form class="form" action="edit_account.php" method="POST" enctype="multipart/form-data">
            <div class="form-group mb-3" style="max-width: 250px; margin: 0 auto 15px auto;">
              <input type="file" name="file_upload" multiple="multiple" class="form-control-file btn btn-sm btn-light border w-100"/>
            </div>
            <div class="form-group mb-0">
              <input type="hidden" name="user_id" value="<?php echo $user['id'];?>">
              <button type="submit" name="submit" class="btn btn-warning btn-md px-4 shadow-sm">
                <i class="glyphicon glyphicon-refresh"></i> Update Photo
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Right Column: Account Info -->
    <div class="col-lg-7 col-md-12">
      <div class="card shadow-sm">
        <div class="card-header py-3 d-flex align-items-center">
          <i class="glyphicon glyphicon-edit" style="margin-right: 8px;"></i>
          <h6 class="m-0 font-weight-bold text-primary">Account Information</h6>
        </div>
        <div class="card-body p-4">
          <form method="post" action="edit_account.php?id=<?php echo (int)$user['id'];?>">
            <div class="form-group mb-3">
              <label for="name" class="form-label font-weight-bold text-secondary">Full Name</label>
              <input type="text" class="form-control" name="name" value="<?php echo remove_junk(ucwords($user['name'])); ?>" placeholder="Enter full name" required>
            </div>
            
            <div class="form-group mb-4">
              <label for="username" class="form-label font-weight-bold text-secondary">Username</label>
              <input type="text" class="form-control" name="username" value="<?php echo remove_junk(ucwords($user['username'])); ?>" placeholder="Enter username" required>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex justify-content-between align-items-center">
              <button type="submit" name="update" class="btn btn-primary px-4 shadow-sm">
                <i class="glyphicon glyphicon-ok"></i> Save Changes
              </button>
              <a href="change_password.php" title="change password" class="btn btn-outline-danger shadow-sm">
                <i class="glyphicon glyphicon-lock"></i> Change Password
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>