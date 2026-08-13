<?php
  $page_title = 'Product List';
  require_once('includes/load.php');
  // permission
  page_require_level(2);

  $products = join_product_table();
  $categories = find_all('categories');
  $selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
  if ($selected_category !== '') {
    $selected_category_l = strtolower($selected_category);
    $products = array_filter($products, function($product) use ($selected_category_l) {
      return strtolower(trim($product['categorie'])) === $selected_category_l;
    });
  }
?>
<?php include_once('layouts/header.php'); ?>

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
            <span>Item List</span>
          </strong>
          
        </div>
        <div class="panel-body">
          <div class="filter-panel" style="margin-bottom: 15px; display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap;">
            <div style="flex:1; min-width:240px;">
              <label for="productListSearch" style="display:block; font-weight:600; margin-bottom:4px;">Search</label>
              <input id="productListSearch" type="search" class="form-control" placeholder="Search items..." />
            </div>
            <div style="display:flex; gap:10px; align-items:flex-end;">
              <div style="min-width:220px;">
                <label for="categoryFilter" style="display:block; font-weight:600; margin-bottom:4px;">Category</label>
                <select id="categoryFilter" name="category" class="form-control">
                  <option value="">All Categories</option>
                  <?php foreach($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php if($selected_category === $category['name']) echo 'selected'; ?>><?php echo remove_junk($category['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div style="min-width:140px;">
                <label style="display:block; visibility:hidden; height:1px;">&nbsp;</label>
                <a href="add_product.php" class="btn btn-success btn-block" style="white-space:nowrap;">
                  <span class="glyphicon glyphicon-plus"></span> Add Item
                </a>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
              <thead>
                <tr>
                  <th class="text-center" style="width: 50px;">#</th>
                  <th>Item Name</th>
                  <th>Item Category</th>
                  <th class="text-center">Serial Number</th>
                  <th class="text-center">Receipt Number</th>
                  <th class="text-center">Total Quantity</th>
                  <th class="text-center">Price / each</th>
                  <th class="text-center">Total Price</th>
                  <th class="text-center">Unit</th>
                  <th class="text-center">Image</th>
                  <th class="text-center">Date</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($products)): ?>
                  <tr>
                    <td colspan="12" class="text-center">No Items found.</td>
                  </tr>
                <?php else: ?>
                  <?php $count = 1; ?>
                  <?php foreach($products as $product): ?>
                    <?php $quantity = (int)$product['quantity'];
                      $serial = !empty($product['serial_number']) ? remove_junk($product['serial_number']) : '-';
                      $receipt = !empty($product['receipt_number']) ? remove_junk($product['receipt_number']) : '-';
                    ?>
                    <tr>
                      <td class="text-center"><?php echo $count++; ?></td>
                      <td><?php echo remove_junk(first_character($product['name'])); ?></td>
                      <td><?php echo remove_junk(first_character($product['categorie'])); ?></td>
                      <td class="text-center"><?php echo $serial; ?></td>
                      <td class="text-center"><?php echo $receipt; ?></td>
                      <td class="text-center" style="white-space: nowrap;">
                        <strong><?php echo $quantity; ?></strong>
                        <span class="label label-default" style="font-size: 11px; margin-left: 3px;">
                          <?php echo !empty($product['unit']) ? remove_junk($product['unit']) : 'piece'; ?>
                        </span>
                      </td>

                      <?php $unit_price = (float)$product['buy_price']; $total_price = $unit_price * $quantity; ?>
                      <td class="text-center">PHP <?php echo number_format($unit_price, 2); ?></td>
                      <td class="text-center">PHP <?php echo number_format($total_price, 2); ?></td>
                      <td class="text-center"><?php echo !empty($product['unit']) ? remove_junk($product['unit']) : 'piece'; ?></td>
                      <td class="text-center">
                        <?php
                          $image_file = isset($product['image']) ? trim($product['image']) : '';
                          $image_file = $image_file !== '' ? basename($image_file) : '';
                          $image_path = $image_file !== '' ? 'uploads/products/' . $image_file : 'uploads/products/no_image.png';
                          $image_file_path = $image_file !== '' ? __DIR__ . '/uploads/products/' . $image_file : __DIR__ . '/uploads/products/no_image.png';
                          $has_image = !empty($image_file) && file_exists($image_file_path);
                        ?>
                        <?php if($has_image): ?>
                          <img src="<?php echo htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8'); ?>" alt="Product Image" class="zoomable-product-image" data-full-src="<?php echo htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8'); ?>" style="height:36px; width:36px; object-fit:cover; border-radius:4px; cursor:pointer;" />
                        <?php else: ?>
                          <img src="uploads/products/no_image.png" alt="No Image" style="height:36px; width:36px; object-fit:cover; border-radius:4px;" />
                        <?php endif; ?>
                      </td>
                      <td class="text-center"><?php echo read_date($product['date']); ?></td>

                      <td class="text-center">
                        <div class="btn-group" role="group">
                          <a href="edit_product.php?id=<?php echo (int)$product['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                          <a href="delete_product.php?id=<?php echo (int)$product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
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

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('productListSearch');
    var categoryFilter = document.getElementById('categoryFilter');
    var rows = document.querySelectorAll('.table-responsive tbody tr');
    if (!searchInput || !categoryFilter || rows.length === 0) return;

    function applyFilter() {
      var query = searchInput.value.toLowerCase().trim();
      var category = categoryFilter.value.toLowerCase().trim();

      rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var matchesQuery = query === '' || text.indexOf(query) !== -1;
        var matchesCategory = category === '' || text.indexOf(category) !== -1;
        row.style.display = matchesQuery && matchesCategory ? '' : 'none';
      });
    }

    searchInput.addEventListener('input', applyFilter);
    categoryFilter.addEventListener('change', applyFilter);
  });
</script>

<?php include_once('layouts/footer.php'); ?>
