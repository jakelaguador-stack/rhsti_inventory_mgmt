<?php
  $page_title = 'Stock List';
  require_once('includes/load.php');
  page_require_level(2);

  // 1. Kumuha muna ng lahat ng produkto para hindi mawala ang mga dropdown options
  $all_products = join_product_table();

  // Handle AJAX POST to update used value
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_used') {
    header('Content-Type: application/json');
    $p_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $new_used = isset($_POST['used']) ? (int)$_POST['used'] : 0;
    if ($p_id <= 0) {
      echo json_encode(['success' => false, 'error' => 'Invalid product id']);
      exit;
    }
    $product = find_by_id('products', $p_id);
    if (!$product) {
      echo json_encode(['success' => false, 'error' => 'Product not found']);
      exit;
    }
    $old_used = isset($product['used']) ? (int)$product['used'] : 0;
    $ok = adjust_product_used($new_used, $old_used, $p_id);
    if ($ok) {
      // Fetch updated values to return
      $updated = find_by_id('products', $p_id);
      $quantity = (int)$updated['quantity'];
      $used = isset($updated['used']) ? (int)$updated['used'] : 0;
      $available = max(0, $quantity - $used);
      echo json_encode(['success' => true, 'used' => $used, 'available' => $available]);
      exit;
    } else {
      echo json_encode(['success' => false, 'error' => 'Failed to update used value']);
      exit;
    }
  }

    // 2. Kumuha ng mga malilinis na natatanging kategorya (id => name) para sa dropdown filter
    $categories = [];
    if (!empty($all_products)) {
      foreach ($all_products as $p) {
        $cat_id = isset($p['categorie_id']) ? (int)$p['categorie_id'] : 0;
        $cat_name = isset($p['categorie']) ? trim($p['categorie']) : '';
        if ($cat_id > 0 && $cat_name !== '') {
          $categories[$cat_id] = $cat_name;
        }
      }
      // Sort by category name while preserving keys
      asort($categories, SORT_NATURAL | SORT_FLAG_CASE);
    }

    // 3. I-filter ang mga produkto base sa napiling kategorya (by ID)
    $selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $products = $all_products; // Default na ipapakita ang lahat kung walang filter

    if ($selected_category > 0) {
      $products = array_filter($all_products, function($product) use ($selected_category) {
        return isset($product['categorie_id']) && (int)$product['categorie_id'] === $selected_category;
      });
    }

    // 4. Server-side search filtering (optional)
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($search !== '') {
      $search_l = strtolower($search);
      $products = array_filter($products, function($product) use ($search_l) {
        $name = strtolower((string)($product['name'] ?? ''));
        $cat  = strtolower((string)($product['categorie'] ?? ''));
        $unit = strtolower((string)($product['unit'] ?? ''));
        return (strpos($name, $search_l) !== false) || (strpos($cat, $search_l) !== false) || (strpos($unit, $search_l) !== false);
      });
    }

    // If this is an AJAX live-search request, return only the table rows HTML
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
      if (empty($products)) {
        echo "<tr>\n<td colspan=\"8\" class=\"text-center\">No stock items found.</td>\n</tr>\n";
      } else {
        $count = 1;
        foreach ($products as $product) {
          $pid = (int)$product['id'];
          $quantity = (int)$product['quantity'];
          $used = isset($product['used']) ? (int)$product['used'] : 0;
          $available = max(0, $quantity - $used);
          $unit = !empty($product['unit']) ? remove_junk($product['unit']) : 'piece';
          $unit_price = (float)$product['buy_price'];
          $total_price = $unit_price * $available;
          $name = htmlspecialchars(remove_junk(first_character($product['name'])));
          $cat = htmlspecialchars(remove_junk(first_character($product['categorie'])));
          $unit = htmlspecialchars($unit);
          $formatted_total = 'PHP ' . number_format($total_price, 2);
          echo "<tr data-id=\"{$pid}\">\n";
          echo "<td class=\"text-center\">{$count}</td>\n";
          echo "<td>{$name}</td>\n";
          echo "<td>{$cat}</td>\n";
          echo "<td class=\"text-center\">{$available}</td>\n";
          echo "<td class=\"text-center\"><span class=\"used-val\" id=\"used-{$pid}\">{$used}</span></td>\n";
          echo "<td class=\"text-center\">{$unit}</td>\n";
          echo "<td class=\"text-center\">{$formatted_total}</td>\n";
          echo "<td class=\"text-center edit-cell\" data-id=\"{$pid}\"><button type=\"button\" class=\"btn btn-xs btn-link edit-used\" data-id=\"{$pid}\">Edit</button></td>\n";
          echo "</tr>\n";
          $count++;
        }
      }
      exit;
    }
?>
<?php include_once('layouts/header.php'); ?>

<style>
  @media print {
    body * { visibility: hidden !important; }
    #stock-printable, #stock-printable * { visibility: visible !important; }
    #stock-printable { position: absolute; left: 0; top: 0; width: 100%; }
    #stock-printable .form-group, #stock-printable .help-block, #stock-printable .btn, #stock-printable .input-group-addon, #stock-printable input, #stock-printable select, #stock-printable .glyphicon { display: none !important; }
    #stock-printable .panel-heading { display: block !important; }
    #stock-printable .panel-body { display: block !important; padding: 0 !important; }
    #stock-printable .table { width: 100%; border-collapse: collapse !important; }
    #stock-printable .table th, #stock-printable .table td { border: 1px solid #ccc !important; }
    #stock-printable .table tbody tr { page-break-inside: avoid; }
    #stock-printable .table tbody tr[style*="display: none"] { display: none !important; }
  }
</style>

<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>

<div class="row mt-4">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <strong>
          <span class="glyphicon glyphicon-th-list"></span>
          <span>Stock List</span>
        </strong>
      </div>
      <div class="panel-body" id="stock-printable">
        
        <div class="institute-header">
          <img src="uploads/images/logo.jpg" alt="The Ripple of Hope Logo" class="institute-logo">
          <div class="institute-title-block">
            <div class="institute-title">The Ripple of Hope Skills Institute Inc.</div>
            <div class="institute-sub">SYMAR BLDG., VINZONS AVE., BRGY VII, DAET, CAMARINES NORTE</div>
            <div class="institute-details">School I.D. No. 408869</div>
            <h5 class="report-title-badge mb-0 mt-3">Stock List Report</h5>
            <div class="fw-bold text-secondary small">Print Date: <?php echo date("F d, Y"); ?></div>
          </div>
        </div>

        <div class="row" style="margin-bottom: 20px;">
          <form method="GET" action="">
            <div class="col-md-3">
              <div class="form-group">
                <input type="search" name="q" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>" class="form-control" placeholder="Search items, category, unit...">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <select id="category-select" class="form-control" name="category">
                  <option value="">-- Select Category --</option>
                  <?php foreach($categories as $id => $name): ?>
                    <option value="<?php echo (int)$id; ?>" <?php if($selected_category === (int)$id) echo 'selected'; ?>>
                      <?php echo remove_junk(first_character($name)); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <a id="get-items-btn" href="add_sale.php<?php echo $selected_category > 0 ? '?category_id=' . $selected_category : ''; ?>" class="btn btn-warning btn-block">
                <span class="glyphicon glyphicon-shopping-cart"></span> Get Items
              </a>
            </div>
            <div class="col-md-2">
              <button id="print-stock-btn" type="button" class="btn btn-primary btn-block" onclick="printStockList(); return false;">
                <span class="glyphicon glyphicon-print"></span> Print
              </button>
            </div>
            <?php if($selected_category > 0): ?>
              <div class="col-md-2">
                <a href="stock_list.php" class="btn btn-default btn-block">Clear Filter</a>
              </div>
            <?php endif; ?>
          </form>
          <script>
            document.addEventListener('DOMContentLoaded', function(){
              var sel = document.getElementById('category-select');
              var btn = document.getElementById('get-items-btn');
              if(!sel || !btn) return;
              sel.addEventListener('change', function(){
                var val = sel.value;
                btn.href = val ? 'add_sale.php?category_id=' + encodeURIComponent(val) : 'add_sale.php';
                // Trigger AJAX live-search when category changes (fallback to full submit if JS handler not available)
                if (typeof window.stockListDoSearch === 'function') {
                  window.stockListDoSearch();
                } else if (sel.form) {
                  sel.form.submit();
                }
              });
            });
          </script>
          <script>
            document.addEventListener('DOMContentLoaded', function(){
              var searchInput = document.querySelector('input[name="q"]');
              var tbody = document.querySelector('table.table tbody');
              var sel = document.getElementById('category-select');
              var debounceTimer = null;
              if(!searchInput || !tbody) return;
              function doSearch(){
                var q = searchInput.value;
                var category = sel ? sel.value : '';
                var params = new URLSearchParams();
                if(q) params.set('q', q);
                if(category) params.set('category', category);
                params.set('ajax', '1');
                fetch(window.location.pathname + '?' + params.toString(), { credentials: 'same-origin' })
                  .then(function(res){ return res.text(); })
                  .then(function(html){ tbody.innerHTML = html; })
                  .catch(function(){ /* ignore errors */ });
              }
              // expose for other scripts (category change) to call
              window.stockListDoSearch = doSearch;
              searchInput.addEventListener('input', function(){
                clearTimeout(debounceTimer);
                // shorter debounce for near-immediate feedback while typing
                debounceTimer = setTimeout(doSearch, 100);
              });
            });
          </script>
          <script>
            function printStockList() {
              window.print();
            }
          </script>
          <style>
            .institute-header {
              display: flex;
              align-items: center;
              justify-content: center;
              gap: 18px;
              border-bottom: 2px solid #3b82f6;
              padding-bottom: 18px;
              margin-bottom: 25px;
            }
            .institute-logo {
              width: 90px;
              height: 90px;
              object-fit: contain;
            }
            .institute-title-block {
              text-align: center;
            }
            .institute-title {
              font-family: 'Arial Black', Gadget, sans-serif;
              font-size: 1.35rem;
              font-weight: 800;
              color: #1e3a8a;
              margin-bottom: 2px;
              text-transform: uppercase;
            }
            .institute-sub {
              font-size: 0.85rem;
              color: #475569;
              font-weight: 600;
              margin-bottom: 2px;
            }
            .institute-details {
              font-size: 0.75rem;
              color: #64748b;
            }
            .report-title-badge {
              text-align: center;
              margin-top: 15px;
              font-weight: 800;
              color: #0f172a;
              text-transform: uppercase;
              letter-spacing: 0.05em;
            }
          </style>
          <style media="print">
            @page {
              size: A4;
              margin: 10mm;
            }
            body {
              margin: 0;
              padding: 0;
            }
            .institute-header {
              display: flex;
              align-items: center;
              justify-content: center;
              gap: 18px;
              border-bottom: 2px solid #000;
              padding-bottom: 18px;
              margin-bottom: 25px;
            }
            .institute-logo {
              width: 90px;
              height: 90px;
              object-fit: contain;
            }
            .institute-title {
              font-size: 1.15rem;
              font-weight: 800;
              margin-bottom: 3px;
            }
            .institute-sub,
            .institute-details,
            .report-title-badge {
              color: #000;
            }
            .table-responsive {
              overflow: visible !important;
              width: 100%;
            }
            table.table {
              width: 100%;
              border-collapse: collapse;
              font-size: 12px;
            }
            table.table thead,
            table.table tbody {
              display: table-row-group;
            }
            table.table th,
            table.table td {
              border: 1px solid #000;
              padding: 5px;
              page-break-inside: avoid;
            }
            table.table thead th {
              background-color: #f5f5f5;
              font-weight: bold;
              text-align: center;
            }
            table.table tbody tr {
              page-break-inside: avoid;
            }
            .no-print {
              display: none !important;
            }
          </style>
        </div>
        
        <div class="table-responsive">
          <table class="table table-striped table-hover table-bordered">
            <thead>
              <tr>
                <th class="text-center" style="width: 50px;">#</th>
                <th>Item Name</th>
                <th>Category</th>
                <th class="text-center">Available</th>
                <th class="text-center">Used</th>
                <th class="text-center">Unit</th>
                <th class="text-center">Price</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($products)): ?>
                <tr>
                  <td colspan="7" class="text-center">No stock items found.</td>
                </tr>
              <?php else: ?>
                <?php $count = 1; foreach($products as $product): ?>
                  <?php 
                    $quantity = (int)$product['quantity']; 
                    $used = isset($product['used']) ? (int)$product['used'] : 0; 
                    $available = max(0, $quantity - $used); 
                    $unit_price = (float)$product['buy_price'];
                    $total_price = $unit_price * $available;
                    $formatted_total = 'PHP ' . number_format($total_price, 2);
                  ?>
                  <tr data-id="<?php echo (int)$product['id']; ?>" data-unit-price="<?php echo number_format($unit_price, 2, '.', ''); ?>">
                    <td class="text-center"><?php echo $count++; ?></td>
                    <td><?php echo remove_junk(first_character($product['name'])); ?></td>
                    <td><?php echo remove_junk(first_character($product['categorie'])); ?></td>
                    <td class="text-center"><?php echo $available; ?></td>
                    <td class="text-center"><span class="used-val" id="used-<?php echo (int)$product['id']; ?>"><?php echo $used; ?></span></td>
                    <td class="text-center"><?php echo !empty($product['unit']) ? remove_junk($product['unit']) : 'piece'; ?></td>
                    <td class="text-center total-price-cell"><?php echo $formatted_total; ?></td>
                    <td class="text-center edit-cell" data-id="<?php echo (int)$product['id']; ?>"><button type="button" class="btn btn-xs btn-link edit-used" data-id="<?php echo (int)$product['id']; ?>">Edit</button></td>
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


<script>
document.addEventListener('DOMContentLoaded', function(){
  var tbody = document.querySelector('table.table tbody');
  if(!tbody) return;

  tbody.addEventListener('click', function(e){
    var el = e.target;
    if(!el) return;
    if (el.classList.contains('edit-used')) {
      var tr = el.closest('tr');
      var pid = el.getAttribute('data-id');
      if(!tr || !pid) return;
      var usedSpan = tr.querySelector('.used-val');
      var currentUsed = parseInt(usedSpan ? usedSpan.textContent : '0', 10) || 0;
      var availTd = tr.querySelector('td:nth-child(4)');
      var available = availTd ? availTd.textContent.trim() : '0';
      var editCell = tr.querySelector('.edit-cell');
      if(!editCell) return;

      // Replace button with input + save/cancel
      editCell.innerHTML = '';
      editCell.style.display = 'flex';
      editCell.style.alignItems = 'center';
      editCell.style.gap = '5px';
      editCell.style.justifyContent = 'center';
      
      var input = document.createElement('input');
      input.type = 'number';
      input.min = '0';
      input.value = currentUsed;
      input.style.width = '60px';
      input.className = 'form-control input-sm';

      var save = document.createElement('button');
      save.type = 'button';
      save.className = 'btn btn-xs btn-success';
      save.textContent = 'Save';

      var cancel = document.createElement('button');
      cancel.type = 'button';
      cancel.className = 'btn btn-xs btn-default';
      cancel.textContent = 'Cancel';

      editCell.appendChild(input);
      editCell.appendChild(save);
      editCell.appendChild(cancel);

      cancel.addEventListener('click', function(){
        editCell.innerHTML = '<button type="button" class="btn btn-xs btn-link edit-used" data-id="' + pid + '">Edit</button>';
      });

      save.addEventListener('click', function(){
        var newUsed = parseInt(input.value, 10);
        if(isNaN(newUsed) || newUsed < 0){
          alert('Enter a valid non-negative number');
          return;
        }
        save.disabled = true;
        cancel.disabled = true;

        var formData = new URLSearchParams();
        formData.set('action', 'update_used');
        formData.set('id', pid);
        formData.set('used', String(newUsed));

        fetch(window.location.pathname, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: formData.toString(),
          credentials: 'same-origin'
        }).then(function(res){ return res.json(); })
        .then(function(json){
          if(json && json.success){
            if(usedSpan) usedSpan.textContent = json.used;
            if(availTd) availTd.textContent = json.available;
            var totalCell = tr.querySelector('.total-price-cell');
            var unitPrice = parseFloat(tr.dataset.unitPrice || '0');
            if(totalCell && !isNaN(unitPrice)){
              totalCell.textContent = 'PHP ' + (unitPrice * json.available).toFixed(2);
            }
            editCell.innerHTML = '<button type="button" class="btn btn-xs btn-link edit-used" data-id="' + pid + '">Edit</button>';
          } else {
            alert((json && json.error) ? json.error : 'Failed to update');
            cancel.click();
          }
        }).catch(function(){
          alert('Network error');
          cancel.click();
        }).finally(function(){
          save.disabled = false;
          cancel.disabled = false;
        });
      });
    }
  });
});
</script>

<?php include_once('layouts/footer.php'); ?>