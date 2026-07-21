<?php
  $page_title = 'All Group';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
  $all_groups = find_all('user_groups');
?>
<?php include_once('layouts/header.php'); ?>

<style>
  .school-panel {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #eef2f5;
    margin-top: 15px;
    overflow: hidden;
  }
  .school-panel-heading {
    background-color: #ffffff !important;
    border-bottom: 2px solid #f4f6f9 !important;
    padding: 20px 25px !important;
  }
  .school-panel-heading strong {
    font-size: 18px;
    color: #1e3d59; /* Academic Blue */
  }
  .school-panel-heading .glyphicon {
    color: #ff6e40; /* School Accent Orange */
    margin-right: 8px;
  }
  
  /* Table Styling */
  .school-table > thead > tr > th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    padding: 14px 15px !important;
    border-bottom: 2px solid #e2e8f0 !important;
  }
  .school-table > tbody > tr > td {
    padding: 14px 15px !important;
    vertical-align: middle !important;
    color: #334155;
    font-size: 14px;
    border-top: 1px solid #f1f5f9;
  }
  
  /* Badges & Status */
  .badge-active {
    background-color: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
    padding: 5px 10px;
    border-radius: 6px;
    font-weight: 600;
    display: inline-block;
  }
  .badge-deactive {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    padding: 5px 10px;
    border-radius: 6px;
    font-weight: 600;
    display: inline-block;
  }
  .level-indicator {
    background-color: #f1f5f9;
    color: #475569;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
  }

  /* Action Buttons */
  .btn-action-edit {
    background-color: #f59e0b;
    color: #fff !important;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    transition: all 0.2s;
  }
  .btn-action-edit:hover {
    background-color: #d97706;
    box-shadow: 0 4px 8px rgba(217, 118, 6, 0.2);
  }
  .btn-action-delete {
    background-color: #ef4444;
    color: #fff !important;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    transition: all 0.2s;
  }
  .btn-action-delete:hover {
    background-color: #dc2626;
    box-shadow: 0 4px 8px rgba(220, 38, 38, 0.2);
  }
  .btn-add-school {
    background-color: #1e3d59;
    color: #ffffff !important;
    font-weight: 600;
    border-radius: 6px;
    padding: 8px 16px;
    transition: all 0.2s;
    border: none;
  }
  .btn-add-school:hover {
    background-color: #122538;
    box-shadow: 0 4px 10px rgba(18, 37, 56, 0.2);
  }
</style>

<div class="row">
   <div class="col-md-12">
     <?php echo display_msg($msg); ?>
   </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="panel school-panel">
      <div class="panel-heading school-panel-heading clearfix">
        <strong class="pull-left" style="margin-top: 6px;">
          <span class="glyphicon glyphicon-th"></span>
          <span>User Groups Management</span>
        </strong>
         <a href="add_group.php" class="btn btn-add-school pull-right btn-sm">
           <span class="glyphicon glyphicon-plus"></span> Add New Group
         </a>
      </div>
      <div class="panel-body" style="padding: 0;">
        <div class="table-responsive">
          <table class="table school-table" style="margin-bottom: 0;">
            <thead>
              <tr>
                <th class="text-center" style="width: 60px;">#</th>
                <th>Group Name</th>
                <th class="text-center" style="width: 20%;">Group Level</th>
                <th class="text-center" style="width: 15%;">Status</th>
                <th class="text-center" style="width: 120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($all_groups as $a_group): ?>
              <tr>
               <td class="text-center" style="color: #94a3b8; font-weight: 600;"><?php echo count_id();?></td>
               <td style="font-weight: 600; color: #1e293b;"><?php echo remove_junk(ucwords($a_group['group_name']))?></td>
               <td class="text-center">
                 <span class="level-indicator">Level <?php echo remove_junk(ucwords($a_group['group_level']))?></span>
               </td>
               <td class="text-center">
               <?php if($a_group['group_status'] === '1'): ?>
                <span class="badge-active">Active</span>
              <?php else: ?>
                <span class="badge-deactive">Deactivated</span>
              <?php endif;?>
               </td>
               <td class="text-center">
                 <div class="btn-group">
                    <a href="edit_group.php?id=<?php echo (int)$a_group['id'];?>" class="btn btn-xs btn-action-edit" data-toggle="tooltip" title="Edit">
                      <i class="glyphicon glyphicon-pencil"></i>
                   </a>
                    <a href="delete_group.php?id=<?php echo (int)$a_group['id'];?>" class="btn btn-xs btn-action-delete" data-toggle="tooltip" title="Remove" onclick="return confirm('Sigurado ka bang nais mong burahin ang grupong ito?')">
                      <i class="glyphicon glyphicon-remove"></i>
                    </a>
                  </div>
               </td>
              </tr>
            <?php endforeach;?>
           </tbody>   
          </table>
        </div>
      </div>
    </div>
  </div>
</div>  

<?php include_once('layouts/footer.php'); ?>