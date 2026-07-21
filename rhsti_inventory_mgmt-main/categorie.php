<?php
  $page_title = 'All categories';
  require_once('includes/load.php');
  // TANDAAN: Kung hindi ka admin (Level 1), posibleng i-redirect ka nito sa profile/home page.
  // Palitan ang 1 ng 2 o 3 kung gustong payagan ang ibang user levels.
  page_require_level(1); 
  
  $all_categories = find_all('categories');
?>
<?php
 if(isset($_POST['add_cat'])){
   $req_field = array('categorie-name');
   validate_fields($req_field);
   $cat_name = remove_junk($db->escape($_POST['categorie-name']));
   if(empty($errors)){
      $sql  = "INSERT INTO categories (name)";
      $sql .= " VALUES ('{$cat_name}')";
      if($db->query($sql)){
        $session->msg("s", "Successfully Added New Category");
        redirect('categorie.php',false);
      } else {
        $session->msg("d", "Sorry Failed to insert.");
        redirect('categorie.php',false);
      }
   } else {
     $session->msg("d", $errors);
     redirect('categorie.php',false);
   }
 }
?>
<?php include_once('layouts/header.php'); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
  /* Global Inter Font Override */
  .custom-theme-scope, 
  .custom-theme-scope input, 
  .custom-theme-scope button, 
  .custom-theme-scope table,
  .custom-theme-scope label {
    font-family: 'Inter', sans-serif !important;
  }
  
  body {
    background-color: #f8fafc;
  }

  /* Card Layouts */
  .custom-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    overflow: hidden;
  }
  
  .theme-header-green {
    background-color: #f0fdf4; 
    border-bottom: 2px solid #bbf7d0; 
    color: #15803d !important; 
    padding: 1.2rem 1.5rem;
  }

  .theme-header-dark {
    background-color: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 1.2rem 1.5rem;
  }

  .gradient-btn {
    background-color: #10b981; 
    border: 1px solid #047857; 
    color: white;
    font-weight: 600;
    transition: all 0.2s ease;
  }
  .gradient-btn:hover {
    background-color: #059669; 
    color: white;
    opacity: 0.95;
  }

  /* Modernized Tables */
  .table-modern th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    color: #64748b;
    background-color: #fafafa;
    border-bottom: 2px solid #e2e8f0;
    padding: 12px 16px;
  }
  .table-modern td {
    padding: 14px 16px;
    color: #334155;
    font-size: 0.9rem;
    border-bottom: 1px solid #f1f5f9;
  }

  .action-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s;
    text-decoration: none;
  }
  .action-btn-edit {
    background-color: #fef3c7;
    color: #d97706;
  }
  .action-btn-edit:hover {
    background-color: #d97706;
    color: white;
  }
  .action-btn-delete {
    background-color: #fee2e2;
    color: #dc2626;
  }
  .action-btn-delete:hover {
    background-color: #dc2626;
    color: white;
  }

  .form-control-custom {
    border: 2px solid #000000;
    border-radius: 6px;
    padding: 10px 14px;
    background-color: #ffffff;
    font-size: 0.95rem;
  }
  .form-control-custom:focus {
    border-color: #10b981; 
    outline: none;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
  }

  .badge-count {
    background-color: #f1f5f9;
    color: #475569;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.85rem;
  }
</style>

<div class="container-fluid py-4 custom-theme-scope" style="max-width: 1400px;">
  <div class="row mb-4">
     <div class="col-12">
       <?php echo display_msg($msg); ?>
     </div>
  </div>

  <div class="row">
    <div class="col-md-4" style="margin-bottom: 20px;">
      <div class="card custom-card">
        <div class="card-header theme-header-green">
          <h5 class="mb-0 fw-bold d-flex align-items-center" style="font-size: 1.1rem; margin: 0;">
            <i class="fas fa-plus-circle me-2" style="margin-right: 8px;"></i> Add New Category
          </h5>
        </div>
        <div class="card-body p-4">
          <form method="post" action="categorie.php">
            <div class="form-group mb-4" style="margin-bottom: 15px;">
              <label for="categorie-name" class="control-label fw-bold small text-muted" style="display:block; margin-bottom: 8px;">CATEGORY NAME</label>
              <input type="text" class="form-control form-control-custom" id="categorie-name" name="categorie-name" placeholder="e.g. Electronics, Groceries" required autocomplete="off">
            </div>
            <button type="submit" name="add_cat" class="btn gradient-btn w-100 py-2 rounded-3 shadow-sm" style="padding: 10px; width: 100%;">
              <i class="fas fa-check me-2" style="margin-right: 6px;"></i> Save Category
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card custom-card">
        <div class="card-header theme-header-dark d-flex align-items-center justify-content-between" style="display: flex; justify-content: space-between; align-items: center;">
          <h5 class="mb-0 fw-bold text-dark d-flex align-items-center" style="font-size: 1.1rem; margin: 0;">
            <i class="fas fa-folder me-2" style="color: #10b981; margin-right: 8px;"></i> All Categories
          </h5>
          <span class="badge rounded-pill bg-light text-dark border px-3 py-2 fw-semibold" style="padding: 6px 12px; border-radius: 50px; background: #f8fafc; border: 1px solid #e2e8f0;">
            Total: <?php echo count($all_categories); ?>
          </span>
        </div>
        <div class="card-body p-0" style="padding: 0;">
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0" style="width: 100%; margin-bottom: 0;">
              <thead>
                <tr>
                  <th class="text-center" style="width: 80px; text-align: center;">#</th>
                  <th style="text-align: left;">Category Name</th>
                  <th class="text-center" style="width: 140px; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($all_categories)): ?>
                  <tr>
                    <td colspan="3" class="text-center py-5 text-muted" style="text-align: center; padding: 40px 0;">
                      <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" alt="Empty" style="width: 50px; opacity: 0.4;" class="mb-3 d-block mx-auto">
                      <div class="fw-medium" style="margin-top: 10px;">No categories added yet.</div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php $counter = 1; ?>
                  <?php foreach ($all_categories as $cat): ?>
                    <tr>
                      <td class="text-center" style="text-align: center;">
                        <span class="badge-count fw-bold"><?php echo $counter++; ?></span>
                      </td>
                      <td style="text-align: left;">
                        <span class="fw-bold" style="color: #1e293b;"><?php echo remove_junk(ucfirst($cat['name'])); ?></span>
                      </td>
                      <td class="text-center" style="text-align: center;">
                        <div class="d-flex justify-content-center gap-2" style="display: flex; justify-content: center; gap: 8px;">
                          <a href="edit_categorie.php?id=<?php echo (int)$cat['id']; ?>" class="action-btn action-btn-edit" title="Edit Category">
                            <i class="fas fa-pen-to-square"></i>
                          </a>
                          <a href="delete_categorie.php?id=<?php echo (int)$cat['id']; ?>" class="action-btn action-btn-delete" title="Delete Category" onclick="return confirm('Sigurado ka ba na gusto mong burahin ang kategoryang ito?');">
                            <i class="fas fa-trash-can"></i>
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>