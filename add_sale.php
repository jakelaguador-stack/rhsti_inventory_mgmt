<?php
  $page_title = 'Get Items';
  require_once('includes/load.php');
  // Check what level user has permission to view this page
  page_require_level(3);

  $selected_product = null;
  $products = join_product_table();
  $all_categories = find_all('categories');
  $filter_cat = isset($_GET['category_id']) && (int)$_GET['category_id'] > 0 ? (int)$_GET['category_id'] : 0;

  // If a category_id is passed, filter the raw products to that category
  if($filter_cat > 0) {
    $products = array_filter($products, function($p) use ($filter_cat) {
      return isset($p['categorie_id']) && (int)$p['categorie_id'] === $filter_cat;
    });
  }

  // Merge same item rows by name, unit, and category for the displayed Get Items list
  $grouped_products = [];
  foreach ($products as $prod) {
    $prod_name = trim(remove_junk($prod['name']));
    $raw_unit = trim(remove_junk($prod['unit'] ?? ''));
    $unit = $raw_unit !== '' ? $raw_unit : 'piece';
    $display_unit = $unit !== '' ? $unit : 'piece';
    $category = isset($prod['categorie']) && strlen($prod['categorie']) ? remove_junk($prod['categorie']) : 'Uncategorized';
    $category_id = isset($prod['categorie_id']) ? (int)$prod['categorie_id'] : 0;
    $group_key = strtolower($prod_name) . '|' . strtolower($unit) . '|' . $category_id;

    if (!isset($grouped_products[$group_key])) {
      $grouped_products[$group_key] = [
        'id' => $prod['id'],
        'name' => $prod_name,
        'unit' => $unit,
        'display_unit' => $display_unit,
        'categorie' => $category,
        'categorie_id' => $category_id,
        'quantity' => 0,
        'used' => 0,
      ];
    }

    $grouped_products[$group_key]['quantity'] += (int)$prod['quantity'];
    $grouped_products[$group_key]['used'] += isset($prod['used']) ? (int)$prod['used'] : 0;
  }
  $products = array_values($grouped_products);
  
  // Kunin ang napiling produkto base sa URL parameters para sa nasa itaas na form
  if(isset($_GET['group_name']) && strlen($_GET['group_name']) > 0) {
    $selected_unit = trim(remove_junk($db->escape($_GET['group_unit'] ?? '')));
    $selected_product = [
      'name' => remove_junk($db->escape($_GET['group_name'])),
      'unit' => $selected_unit !== '' ? $selected_unit : 'piece',
      'categorie_id' => isset($_GET['group_cat_id']) ? (int)$_GET['group_cat_id'] : 0,
    ];
  }
?>
<?php
  if(isset($_POST['add_sale'])){
    $req_fields = array('group_name','group_cat_id','quantity','date' );
    validate_fields($req_fields);
    if(empty($errors)){
      $s_qty     = $db->escape((int)$_POST['quantity']);
      $date      = remove_junk($db->escape($_POST['date']));
      $s_date    = date('Y-m-d', strtotime($date ?: make_date()));
      $group_name = remove_junk($db->escape($_POST['group_name']));
      $group_unit = remove_junk($db->escape($_POST['group_unit'] ?? ''));
      $group_cat_id = (int)$_POST['group_cat_id'];

      if($s_qty < 1){
        $session->msg('d','Quantity must be at least 1.');
        redirect('add_sale.php?group_name=' . rawurlencode($group_name) . '&group_unit=' . rawurlencode($group_unit) . '&group_cat_id=' . $group_cat_id, false);
      }

      $group_totals = find_product_group_totals($group_name, $group_unit, $group_cat_id);
      $available = $group_totals['available'];
      
      if($s_qty > $available){
        $session->msg('d', "Requested quantity exceeds available stock. Only {$available} item(s) left.");
        redirect('add_sale.php?group_name=' . rawurlencode($group_name) . '&group_unit=' . rawurlencode($group_unit) . '&group_cat_id=' . $group_cat_id, false);
      }

      // -------------------------------------------------------------------------
      // BAGONG PATINGING LOGIC: DEDUCT FROM ACROSS POOLED ROWS (BATCH-BY-BATCH)
      // -------------------------------------------------------------------------
      $p_name = $db->escape($group_name);
      $p_unit = trim($db->escape($group_unit));
      if($p_unit === '') { $p_unit = 'piece'; }
      
      // Kukunin lahat ng rows sa database na kapareho ng Name, Unit, at Category ID
      $sql  = "SELECT id, quantity, used FROM products ";
      $sql .= " WHERE name = '{$p_name}' AND COALESCE(NULLIF(unit,''),'piece') = '{$p_unit}' AND categorie_id = '{$group_cat_id}'";
      $sql .= " ORDER BY id ASC"; // FIFO Method: Unang babawasan ang pinakamatandang stock row
      
      $matching_products = $db->query($sql);
      $remaining_to_deduct = $s_qty;

      // Sisimulan ang database transaction para sigurado at ligtas ang sabayang pag-update
      $db->query("START TRANSACTION");
      // Generate a batch id for this Get Items request so we can group sales
      $batch_id = 'b' . str_replace('.', '', uniqid('', true));
      $success = true;

      while($row = $db->fetch_assoc($matching_products)) {
        if($remaining_to_deduct <= 0) break;

        $row_tot = (int)$row['quantity'];
        $row_used = (int)$row['used'];
        $row_avail = max(0, $row_tot - $row_used);

        if($row_avail > 0) {
          // Alamin kung magkano lang ang pwedeng ibawas sa row na ito
          $deduct_from_this_row = min($remaining_to_deduct, $row_avail);
          $new_used = $row_used + $deduct_from_this_row;
          $row_id = (int)$row['id'];

          // Direktang ia-update ang specific row na ito sa database
          $update_sql = "UPDATE products SET used = '{$new_used}' WHERE id = '{$row_id}'";
          if(!$db->query($update_sql)) {
            $success = false;
            break;
          }

          // Record this deduction as a sales entry so it appears in Used Items (one row per deduction)
          $ins_product_id = (int)$row_id;
          $ins_qty = -1 * (int)$deduct_from_this_row; // negative to indicate removal
          $ins_date = $db->escape($s_date);
          $ins_remark_raw = isset($_POST['remarks']) ? $_POST['remarks'] : '';
          $ins_remark = remove_junk($db->escape($ins_remark_raw));
          // Only include remarks and batch if the columns exist
          $col_rem = $db->query("SHOW COLUMNS FROM sales LIKE 'remarks'");
          $col_batch = $db->query("SHOW COLUMNS FROM sales LIKE 'batch'");
          $has_rem = $col_rem && $col_rem->num_rows > 0;
          $has_batch = $col_batch && $col_batch->num_rows > 0;
          if ($has_rem && $has_batch) {
            $insert_sql = "INSERT INTO sales (product_id, qty, date, remarks, batch) VALUES ('{$ins_product_id}', '{$ins_qty}', '{$ins_date}', '{$ins_remark}', '{$batch_id}')";
          } elseif ($has_batch) {
            $insert_sql = "INSERT INTO sales (product_id, qty, date, batch) VALUES ('{$ins_product_id}', '{$ins_qty}', '{$ins_date}', '{$batch_id}')";
          } elseif ($has_rem) {
            $insert_sql = "INSERT INTO sales (product_id, qty, date, remarks) VALUES ('{$ins_product_id}', '{$ins_qty}', '{$ins_date}', '{$ins_remark}')";
          } else {
            $insert_sql = "INSERT INTO sales (product_id, qty, date) VALUES ('{$ins_product_id}', '{$ins_qty}', '{$ins_date}')";
          }
          if(!$db->query($insert_sql)){
            $success = false;
            break;
          }

          // Ibabawas sa natitirang kailangang i-deduct
          $remaining_to_deduct -= $deduct_from_this_row;
        }
      }

      if($success && $remaining_to_deduct == 0){
        $db->query("COMMIT");
        $session->msg('s',"Item successfully taken from pooled stock. (To view the used items, go to <a href='used_items.php'>used items</a>)");
        redirect('add_sale.php?group_name=' . rawurlencode($group_name) . '&group_unit=' . rawurlencode($group_unit) . '&group_cat_id=' . $group_cat_id, false);
      } else {
        $db->query("ROLLBACK");
        $session->msg('d',' Sorry failed to update item usage across the pool!');
        redirect('add_sale.php?group_name=' . rawurlencode($group_name) . '&group_unit=' . rawurlencode($group_unit) . '&group_cat_id=' . $group_cat_id, false);
      }
      // -------------------------------------------------------------------------
      // END OF NEW LOGIC
      // -------------------------------------------------------------------------
      
    } else {
      $session->msg("d", $errors);
      $redirect_url = 'add_sale.php';
      if(isset($_POST['group_name']) && strlen($_POST['group_name']) > 0) {
        $redirect_url .= '?group_name=' . rawurlencode($_POST['group_name']);
        $redirect_url .= '&group_unit=' . rawurlencode($_POST['group_unit'] ?? '');
        $redirect_url .= '&group_cat_id=' . (int)($_POST['group_cat_id'] ?? 0);
      }
      redirect($redirect_url,false);
    }
  }
?>
<?php include_once('layouts/header.php'); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  body {
    background-color: #f3f4f6 !important;
    font-family: 'Inter', sans-serif;
  }
  .main-container {
    padding: 2rem;
  }
  .page-date-header {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
  }
  /* Card Design base sa screenshot */
  .dispatch-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
    padding: 1.5rem;
  }
  .card-title-custom {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
  }
  .dispatch-title {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }
  .dispatch-title h2 {
    margin: 0;
    font-size: 1.35rem;
    color: #111827;
    letter-spacing: -0.02em;
  }
  .dispatch-title .product-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.75rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 600;
    font-size: 0.95rem;
  }
  .card-title-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.3rem;
  }
  .cancel-link {
    color: #ef4444;
    text-decoration: none;
    font-size: 0.92rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border: 1px solid #fee2e2;
    padding: 0.5rem 0.75rem;
    border-radius: 999px;
    transition: background 0.2s, color 0.2s;
  }
  .cancel-link:hover {
    background: #fef2f2;
    color: #b91c1c;
    text-decoration: none;
  }
  /* Info Row / Summary Bar */
  .info-summary-bar {
    background-color: #ffffff;
    border-radius: 16px;
    padding: 1rem;
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
    display: grid;
    grid-template-columns: repeat(3, minmax(180px, 1fr));
    gap: 1rem;
  }
  .info-card {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    padding: 1rem;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
  }
  .info-card strong {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }
  .info-card .info-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
  }
  .info-card .info-subtext {
    color: #475569;
    font-size: 0.85rem;
  }
  /* Badges at Tags */
  .tag-classification {
    background-color: #d1fae5;
    color: #065f46;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-weight: 500;
  }
  .tag-school {
    background-color: #e0f2fe;
    color: #0369a1;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-weight: 500;
  }
  .tag-kitchen {
    background-color: #fef3c7;
    color: #92400e;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-weight: 500;
  }
  .stock-green {
    background-color: #d1fae5;
    color: #065f46;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
  }
  .stock-red {
    background-color: #fee2e2;
    color: #991b1b;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
  }
  
  /* Inputs & Buttons */
  .form-label-custom {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
  }
  .input-icon-wrapper {
    position: relative;
  }
  .input-icon-wrapper i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
  }
  .form-control-dispatch {
    padding-left: 2.5rem;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    height: 48px;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .form-control-dispatch:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
    outline: none;
  }
  .dispatch-form-group {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.2rem;
  }
  .dispatch-form-note {
    margin-top: 0.6rem;
    color: #475569;
    font-size: 0.88rem;
  }
  .btn-confirm-dispatch {
    background-color: #2563eb;
    color: #ffffff;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    padding: 0.78rem 1.5rem;
    height: 48px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s, transform 0.2s;
  }
  .btn-confirm-dispatch:hover {
    background-color: #1d4ed8;
    color: #ffffff;
    transform: translateY(-1px);
  }
  .btn-confirm-dispatch:disabled {
    background-color: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
    transform: none;
  }
  .dispatch-form-small {
    font-size: 0.84rem;
    color: #64748b;
  }
  .dispatch-card .card-title-actions {
    align-self: center;
  }

  /* Table Customizations */
  .table-dispatcher th {
    background-color: #ffffff !important;
    color: #9ca3af;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #f3f4f6;
    padding: 1rem;
  }
  .table-dispatcher td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
  }
  .item-name-bold {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 2px;
  }
  .item-subtext {
    font-size: 0.75rem;
    color: #9ca3af;
    text-transform: uppercase;
  }

  /* Action Buttons inside table */
  .btn-get-item {
    background-color: #eab308;
    color: white;
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 42px;
    transition: background 0.2s, transform 0.2s;
  }
  .btn-get-item:hover {
    background-color: #ca8a04;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(202, 138, 4, 0.3);
  }
  .btn-out-of-stock {
    background-color: transparent;
    color: #f87171;
    border: 1px solid #fee2e2;
    padding: 0.7rem 1.5rem;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 600;
    pointer-events: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-transform: uppercase;
    min-height: 42px;
  }
  
  /* Filter Search Bar */
  .filter-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
  }
  .filter-wrapper {
    position: relative;
    max-width: 300px;
    flex: 1 1 220px;
  }
  .filter-wrapper i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
  }
  .filter-input {
    padding-left: 2.2rem;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    font-size: 0.9rem;
  }
  .filter-select {
    min-width: 140px;
    max-width: 180px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    font-size: 0.85rem;
    height: 36px;
    padding: 0.3rem 0.6rem;
  }
</style>

<div class="container-fluid main-container">
  
  <div class="page-date-header">
    <?php echo date('F j, Y, g:i a'); ?>
  </div>

  <div class="row">
    <div class="col-12">
      <?php echo display_msg($msg); ?>
    </div>
  </div>

  <div class="dispatch-card">
    <div class="card-title-custom">
      <div class="dispatch-title">
        <h2>Dispatch Stock Request</h2>
        <span class="product-tag"><i class="fa-solid fa-boxes-stacked"></i> <?php echo $selected_product ? remove_junk($selected_product['name']) : 'Monitor'; ?></span>
      </div>
      <div class="card-title-actions">
        <a href="add_sale.php" class="cancel-link"><i class="fa-solid fa-xmark"></i> Cancel</a>
      </div>
    </div>

    <?php 
      // Kalkulahin ang real-time stock ng kasalukuyang piniling aytem
      $current_avail = 0;
      $current_unit = 'piece';
      $current_total = 0;
      $current_used = 0;
      
      if($selected_product) {
        $group_unit = !empty($selected_product['unit']) ? $selected_product['unit'] : '';
        $group_cat_id = isset($selected_product['categorie_id']) ? (int)$selected_product['categorie_id'] : 0;
        $group_totals = find_product_group_totals($selected_product['name'], $group_unit, $group_cat_id);
        $current_avail = $group_totals['available'];
        $current_unit = !empty($selected_product['unit']) ? $selected_product['unit'] : 'piece';
        $current_total = $group_totals['quantity'];
        $current_used = $group_totals['used'];
      }
    ?>

    <div class="info-summary-bar">
      <div class="info-card">
        <strong>Available Inventory</strong>
        <div class="info-value"><?php echo $current_avail; ?> <?php echo $current_unit; ?></div>
        <div class="info-subtext">Ready for dispatch</div>
      </div>
      <div class="info-card">
        <strong>Used Items</strong>
        <div class="info-value"><?php echo $current_used; ?> <?php echo $current_unit; ?></div>
        <div class="info-subtext">Already Used</div>
      </div>
      <div class="info-card">
        <strong>Total Items</strong>
        <div class="info-value"><?php echo $current_total; ?> <?php echo $current_unit; ?></div>
        <div class="info-subtext">Total Items</div>
      </div>
    </div>

    <form method="post" action="add_sale.php" class="dispatch-form-group">
      <input type="hidden" name="group_name" value="<?php echo $selected_product ? remove_junk($selected_product['name']) : ''; ?>">
      <input type="hidden" name="group_unit" value="<?php echo $selected_product ? remove_junk($selected_product['unit'] ?? '') : ''; ?>">
      <input type="hidden" name="group_cat_id" value="<?php echo $selected_product ? (int)$selected_product['categorie_id'] : 0; ?>">
      
      <div class="row align-items-end g-3">
        <div class="col-md-4">
          <label class="form-label-custom">Quantity to Pull Out</label>
          <div class="input-icon-wrapper">
            <i class="fa-solid fa-box"></i>
            <input type="number" class="form-control form-control-dispatch" name="quantity" min="1" max="<?php echo $current_avail; ?>" placeholder="Enter items..." required <?php if($current_avail <= 0) echo 'disabled'; ?>>
          </div>
          <p class="dispatch-form-small">Max available: <?php echo $current_avail; ?> <?php echo $current_unit; ?></p>
        </div>
        
        <div class="col-md-4">
          <label class="form-label-custom">Allocation Date</label>
          <div class="input-icon-wrapper">
            <i class="fa-regular fa-calendar-days"></i>
            <input type="date" class="form-control form-control-dispatch" name="date" value="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <p class="dispatch-form-small">Use today or pick a future date.</p>
        </div>

        <div class="col-md-4 d-grid">
          <button type="submit" name="add_sale" class="btn btn-confirm-dispatch w-100" <?php if(!$selected_product || $current_avail <= 0) echo 'disabled'; ?>>
            <i class="fa-solid fa-truck-ramp-box"></i> Confirm Dispatch Request
          </button>
        </div>
      </div>
    </form>
  </div>


  <div class="dispatch-card">
    <div class="card-title-custom mb-1">
      <i class="fa-solid fa-hubspot" style="color: #3b82f6;"></i> Available Items Dispatcher
    </div>
    <p class="text-muted small mb-4">Select a matched cluster group to fulfill stock pullouts.</p>

    <form method="get" action="add_sale.php" class="filter-controls">
      <div class="filter-wrapper">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="tableFilter" class="form-control filter-input" placeholder="Filter parameters by name, unit, code...">
      </div>
      <select name="category_id" class="form-control filter-select" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach($all_categories as $cat): ?>
          <?php $cat_id = isset($cat['id']) ? (int)$cat['id'] : 0; ?>
          <?php $cat_name = isset($cat['name']) ? remove_junk($cat['name']) : 'Uncategorized'; ?>
          <option value="<?php echo $cat_id; ?>" <?php echo $filter_cat === $cat_id ? 'selected' : ''; ?>>
            <?php echo $cat_name; ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if(isset($_GET['group_name']) && strlen($_GET['group_name']) > 0): ?>
        <input type="hidden" name="group_name" value="<?php echo rawurlencode($_GET['group_name']); ?>">
        <input type="hidden" name="group_unit" value="<?php echo rawurlencode($_GET['group_unit'] ?? ''); ?>">
        <input type="hidden" name="group_cat_id" value="<?php echo isset($_GET['group_cat_id']) ? (int)$_GET['group_cat_id'] : 0; ?>">
      <?php endif; ?>
    </form>

    <div class="table-responsive">
      <table class="table table-dispatcher" id="dispatcherTable">
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Category</th>
            <th>Available Stock</th>
            <th>Used</th>
            <th class="text-center">Actions Control</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($products)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted">No operational items found inside log warehouse.</td>
            </tr>
          <?php else: ?>
            <?php foreach($products as $prod): 
              $tot_qty = (int)$prod['quantity'];
              $used_qty = isset($prod['used']) ? (int)$prod['used'] : 0;
              $avail_qty = max(0, $tot_qty - $used_qty);
              $unit_str = !empty($prod['unit']) ? $prod['unit'] : 'piece';
            ?>
              <tr>
                <td>
                  <div class="item-name-bold"><?php echo remove_junk($prod['name']); ?></div>
                  <div class="item-subtext">Standard Metric: <?php echo strtoupper($unit_str); ?></div>
                </td>
                <td>
                  <div class="item-subtext"><?php echo remove_junk($prod['categorie'] ?? 'Uncategorized'); ?></div>
                </td>
                <td>
                  <?php if($avail_qty > 0): ?>
                    <span class="stock-green"><i class="fa-solid fa-circle-check"></i> <?php echo $avail_qty; ?> <?php echo $unit_str; ?></span>
                  <?php else: ?>
                    <span class="stock-red"><i class="fa-solid fa-circle-xmark"></i> 0 piece</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php echo $used_qty; ?> <?php echo $unit_str; ?>
                </td>
                <td class="text-center">
                  <?php if($avail_qty > 0): ?>
                    <a href="add_sale.php?group_name=<?php echo rawurlencode($prod['name']); ?>&group_unit=<?php echo rawurlencode($prod['unit']); ?>&group_cat_id=<?php echo (int)$prod['categorie_id']; ?>" class="btn btn-get-item">
                      <i class="fa-solid fa-cart-shopping"></i> Get Item
                    </a>
                  <?php else: ?>
                    <button class="btn btn-out-of-stock" disabled>
                      <i class="fa-solid fa-ban"></i> Out of Stock
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const filterInput = document.getElementById('tableFilter');
    const tableRows = document.querySelectorAll('#dispatcherTable tbody tr');

    if(filterInput) {
      filterInput.addEventListener('keyup', function() {
        const query = this.value.toLowerCase().trim();
        
        tableRows.forEach(row => {
          const text = row.textContent.toLowerCase();
          if(text.includes(query)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
    }
  });
</script>

<?php include_once('layouts/footer.php'); ?>