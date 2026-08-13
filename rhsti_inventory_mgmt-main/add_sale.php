<?php
  $page_title = 'Get Items';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(3);

  $selected_product = null;
  $selected_category = '';
  if(isset($_GET['id']) && (int)$_GET['id'] > 0){
    $selected_product = find_by_id('products', (int)$_GET['id']);
    if($selected_product && isset($selected_product['categorie_id'])){
      $category_info = find_by_id('categories', (int)$selected_product['categorie_id']);
      $selected_category = $category_info ? remove_junk($category_info['name']) : 'Uncategorized';
    }
  }
?>
<?php
  if(isset($_POST['add_sale'])){
    $req_fields = array('s_id','quantity','price','total', 'date' );
    validate_fields($req_fields);
        if(empty($errors)){
          $p_id      = $db->escape((int)$_POST['s_id']);
          $s_qty     = $db->escape((int)$_POST['quantity']);
          $s_total   = $db->escape($_POST['total']);
          $date      = remove_junk($db->escape($_POST['date']));
          $s_date    = date('Y-m-d', strtotime($date ?: make_date()));

          $product = find_by_id('products', $p_id);
          if(!$product){
            $session->msg('d','Product not found.');
            redirect('add_sale.php', false);
          }

          if($s_qty < 1){
            $session->msg('d','Quantity must be at least 1.');
            redirect('add_sale.php', false);
          }

          $available = (int)$product['quantity'] - (int)($product['used'] ?? 0);
          if($s_qty > $available){
            $session->msg('d', "Requested quantity exceeds available stock. Only {$available} item(s) left.");
            redirect('add_sale.php', false);
          }

          $updated = update_product_used($s_qty, $p_id);
          if($updated){
                  $session->msg('s',"Item successfully taken.");
                  redirect('product.php', false);
                } else {
                  $session->msg('d',' Sorry failed to update item usage!');
                  redirect('add_sale.php', false);
                }
        } else {
          $session->msg("d", $errors);
          redirect('add_sale.php',false);
        }
  }
?>
<?php include_once('layouts/header.php'); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  body {
    background-color: #f3f4f6; /* Soft cool gray background */
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }
  
  .custom-card {
    border: none;
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05), 0 5px 15px rgba(0, 0, 0, 0.03);
    overflow: hidden;
  }

  .form-control-custom {
    border: 2px solid #e5e7eb;
    border-radius: 12px 0 0 12px !important;
    padding: 0.85rem 1.25rem;
    font-size: 0.95rem;
    transition: all 0.25s ease;
    background-color: #f9fafb;
  }

  .form-control-custom:focus {
    border-color: #6366f1;
    background-color: #fff;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
  }

  .search-btn {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    border: none;
    color: white;
    font-weight: 600;
    border-radius: 0 12px 12px 0 !important;
    padding: 0 1.75rem;
    transition: all 0.25s ease;
  }

  .search-btn:hover {
    background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
  }

  /* Table Inputs Styling */
  .table-input {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    text-align: center;
  }
  
  .table-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    outline: none;
  }

  /* Hide number input spinners for clean look */
  .table-input::-webkit-outer-spin-button,
  .table-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  /* Dropdown AJAX results */
  #result {
    position: absolute;
    z-index: 1000;
    width: 100%;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    margin-top: 8px;
    overflow: hidden;
    padding: 0.25rem;
  }

  #result .list-group-item {
    border: none;
    padding: 0.85rem 1.25rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
    font-size: 0.95rem;
    color: #374151;
  }

  #result .list-group-item:hover {
    background-color: #f5f3ff;
    color: #4f46e5;
    font-weight: 600;
  }

  /* Table Styling */
  .table-modern {
    border-collapse: separate;
    border-spacing: 0;
  }

  .table-modern th {
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.06em;
    color: #4b5563;
    background-color: #f9fafb;
    border-bottom: 2px solid #f3f4f6;
    padding: 1.1rem 1rem;
  }

  .table-modern td {
    padding: 1.25rem 1rem;
    color: #1f2937;
    border-bottom: 1px solid #f3f4f6;
  }

  .btn-action-submit {
    background: #10b981;
    border: none;
    color: white;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.6rem 1.25rem;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    width: 100%;
  }

  .btn-action-submit:hover {
    background: #059669;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
    color: white;
  }

  .empty-state-icon {
    background: #f3f4f6;
    color: #9ca3af;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem auto;
  }
</style>

<div class="container-fluid py-5" style="max-width: 1300px;">
  <div class="row mb-4">
    <div class="col-md-12">
      <?php echo display_msg($msg); ?>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card custom-card">
        <div class="card-header bg-white py-4 px-4 border-0 border-bottom">
          <div class="row align-items-center g-4">
            <div class="col-xl-5 col-lg-4">
              <h4 class="mb-1 fw-bold text-dark d-flex align-items-center">
                <i class="fas fa-boxes-stacked me-3" style="color: #4f46e5; font-size: 1.5rem;"></i> Get Item
              </h4>
              <p class="text-muted small mb-0">Search and fetch products to record item distribution or updates.</p>
            </div>
            
            <div class="col-xl-7 col-lg-8">
              <form method="post" action="ajax.php" autocomplete="off" id="sug-form" class="position-relative">
                <div class="input-group shadow-sm rounded-3">
                  <span class="input-group-text border-2 border-end-0 bg-light" style="border-color: #e5e7eb; border-radius: 12px 0 0 12px; color: #9ca3af;">
                    <i class="fas fa-search"></i>
                  </span>
                  <input type="text" id="sug_input" class="form-control form-control-custom border-start-0" name="title" placeholder="Type item name or barcode to search...">
                  <input type="hidden" id="p_id" name="p_id" value="">
                  <button type="submit" class="btn search-btn">
                    Find It
                  </button>
                </div>
                <div id="result" class="list-group"></div>
              </form>
            </div>
          </div>
        </div>

        <div class="card-body p-4 bg-white">
          <form method="post" action="add_sale.php">
            <div class="table-responsive rounded-3 border" style="border-color: #e5e7eb !important;">
              <table class="table table-modern align-middle mb-0">
                <thead>
                  <tr>
                    <th>Item Name</th>
                    <th class="text-center" style="width: 15%;">Category</th>
                    <th class="text-center" style="width: 15%;">Price</th>
                    <th class="text-center" style="width: 12%;">Qty</th>
                    <th class="text-center" style="width: 18%;">Total</th>
                    <th class="text-center" style="width: 18%;">Date</th>
                    <th class="text-center" style="width: 12%;">Action</th>
                  </tr>
                </thead>
                <tbody id="product_info">
                  <?php if($selected_product): ?>
                    <tr>
                      <td id="s_name" class="fw-semibold text-dark">
                        <?php echo remove_junk($selected_product['name']); ?>
                        <input type="hidden" name="s_id" value="<?php echo (int)$selected_product['id']; ?>">
                      </td>
                      <td class="text-center text-muted">
                        <?php echo $selected_category ?: 'Uncategorized'; ?>
                      </td>
                      <td>
                        <input type="number" class="form-control table-input mx-auto" style="max-width: 130px;" name="price" value="<?php echo (float)$selected_product['buy_price']; ?>" step="0.01" required>
                      </td>
                      <td id="s_qty">
                        <input type="number" class="form-control table-input mx-auto" style="max-width: 90px;" name="quantity" value="1" min="1" required>
                      </td>
                      <td>
                        <input type="text" class="form-control table-input mx-auto fw-bold text-secondary bg-light" style="max-width: 150px;" name="total" value="<?php echo number_format((float)$selected_product['buy_price'], 2); ?>" readonly>
                      </td>
                      <td>
                        <input type="date" class="form-control table-input mx-auto" style="max-width: 160px;" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                      </td>
                      <td>
                        <button type="submit" name="add_sale" class="btn btn-action-submit shadow-sm">
                          <i class="fas fa-check me-1"></i> Get
                        </button>
                      </td>
                    </tr>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center py-5 text-muted">
                        <div class="empty-state-icon">
                          <i class="fas fa-barcode fa-lg"></i>
                        </div>
                        <span class="fw-semibold d-block text-dark mb-1">No Item Selected</span>
                        <span class="small text-muted">Use the smart search bar above to look up products.</span>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>