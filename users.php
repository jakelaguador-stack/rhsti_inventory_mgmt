<?php
  $page_title = 'User Management';
  require_once('includes/load.php');
  page_require_level(1);
  $all_users = find_all_user();
?>
<?php include_once('layouts/header.php'); ?>

<style>
.card-custom{
  border:none;
  border-radius:15px;
  box-shadow:0 4px 20px rgba(0,0,0,.08);
}

.page-title{
  font-size:24px;
  font-weight:700;
}

.user-badge{
  padding:6px 12px;
  border-radius:20px;
  font-size:12px;
}

.status-active{
  background:#d4edda;
  color:#155724;
}

.status-inactive{
  background:#f8d7da;
  color:#721c24;
}

.table thead{
  background:#f8f9fa;
}

.action-btn{
  border-radius:8px;
  margin:0 2px;
}

.user-avatar{
  width:40px;
  height:40px;
  border-radius:50%;
  background:#0d6efd;
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:bold;
}
</style>

<div class="container-fluid mt-4">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
          <h2 class="page-title mb-0">👥 User Management</h2>
          <small class="text-muted">Manage all system users</small>
      </div>

      <a href="add_user.php" class="btn btn-primary">
          ➕ Add New User
      </a>
  </div>

  <?php echo display_msg($msg); ?>

  <div class="card card-custom">
      <div class="card-body">

          <div class="table-responsive">
              <table class="table table-hover align-middle">

                  <thead>
                      <tr>
                          <th>#</th>
                          <th>User</th>
                          <th>Username</th>
                          <th class="text-center">Role</th>
                          <th class="text-center">Status</th>
                          <th>Last Login</th>
                          <th class="text-center">Actions</th>
                      </tr>
                  </thead>

                  <tbody>

                  <?php foreach($all_users as $a_user): ?>

                      <tr>

                          <td><?php echo count_id(); ?></td>

                          <td>
                              <div class="d-flex align-items-center">

                                  <div class="user-avatar me-2">
                                      <?php echo strtoupper(substr($a_user['name'],0,1)); ?>
                                  </div>

                                  <div>
                                      <strong>
                                          <?php echo remove_junk(ucwords($a_user['name'])); ?>
                                      </strong>
                                  </div>

                              </div>
                          </td>

                          <td>
                              <?php echo remove_junk($a_user['username']); ?>
                          </td>

                          <td class="text-center">
                              <span class="badge bg-info">
                                  <?php echo remove_junk($a_user['group_name']); ?>
                              </span>
                          </td>

                          <td class="text-center">

                              <?php if($a_user['status'] === '1'): ?>
                                  <span class="user-badge status-active">
                                      ● Active
                                  </span>
                              <?php else: ?>
                                  <span class="user-badge status-inactive">
                                      ● Inactive
                                  </span>
                              <?php endif; ?>

                          </td>

                          <td>
                              <?php echo read_date($a_user['last_login']); ?>
                          </td>

                          <td class="text-center">

                              <a href="edit_user.php?id=<?php echo (int)$a_user['id'];?>"
                                 class="btn btn-warning btn-sm action-btn"
                                 title="Edit">
                                  ✏️
                              </a>

                              <a href="delete_user.php?id=<?php echo (int)$a_user['id'];?>"
                                 class="btn btn-danger btn-sm action-btn"
                                 onclick="return confirm('Are you sure you want to delete this user?');"
                                 title="Delete">
                                  🗑️
                              </a>

                          </td>

                      </tr>

                  <?php endforeach; ?>

                  </tbody>

              </table>
          </div>

      </div>
  </div>

</div>

<?php include_once('layouts/footer.php'); ?>