<?php
  $page_title = 'Home Page';
  require_once('includes/load.php');
  if (!$session->isUserLoggedIn(true)) { redirect('index.php', false);}

  $products = join_product_table();
  $grouped_items = [];
  foreach ($products as $prod) {
    $prod_name = trim(remove_junk($prod['name']));
    $raw_unit = trim(remove_junk($prod['unit'] ?? ''));
    $unit = $raw_unit !== '' ? $raw_unit : 'piece';
    $category = isset($prod['categorie']) && strlen($prod['categorie']) ? remove_junk($prod['categorie']) : 'Uncategorized';
    $category_id = isset($prod['categorie_id']) ? (int)$prod['categorie_id'] : 0;
    $group_key = strtolower($prod_name) . '|' . strtolower($unit) . '|' . $category_id;

    if (!isset($grouped_items[$group_key])) {
      $grouped_items[$group_key] = [
        'name' => $prod_name,
        'unit' => $unit,
        'categorie' => $category,
        'categorie_id' => $category_id,
        'quantity' => 0,
        'used' => 0,
      ];
    }

    $grouped_items[$group_key]['quantity'] += (int)$prod['quantity'];
    $grouped_items[$group_key]['used'] += isset($prod['used']) ? (int)$prod['used'] : 0;
  }
  $grouped_items = array_values($grouped_items);
?>
<?php include_once('layouts/header.php'); ?>
<style>
  .dashboard-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }
  .dashboard-card h3 {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
    color: #0f172a;
  }
  .dispatcher-table th,
  .dispatcher-table td {
    vertical-align: middle !important;
  }
  .dispatcher-table th {
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .dispatcher-badge {
    display: inline-block;
    padding: 0.45rem 0.85rem;
    border-radius: 9999px;
    background: #eef2ff;
    color: #1d4ed8;
    font-weight: 700;
    font-size: 0.88rem;
  }
  .dispatcher-actions .btn {
    min-width: 110px;
  }
</style>
<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
 <div class="col-md-12">
    <div class="panel">
      <div class="jumbotron text-center">
         <h1>Welcome User <hr> Inventory Management System</h1>
         <p>Browse around to find out the pages that you can access!</p>
      </div>
    </div>
 </div>
</div>
<div class="row">
  <div class="col-md-12">
    <div class="dashboard-card">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
          <h3>Get Items</h3>
          <p class="text-muted">Select a matched cluster group to fulfill stock pullouts.</p>
        </div>
        <div class="dispatcher-badge">Available Items: <?php echo count($grouped_items); ?></div>
      </div>
      <div class="row mb-3 gx-2 gy-2">
        <div class="col-md-6">
          <input id="dashboardItemSearch" type="search" class="form-control" placeholder="Search item name, category or unit...">
        </div>
        <div class="col-md-3">
          <select id="dashboardCategoryFilter" class="form-control">
            <option value="">All categories</option>
            <?php
              $categories = [];
              foreach ($grouped_items as $item) {
                $categoryName = remove_junk(first_character($item['categorie']));
                if ($categoryName !== '' && !in_array($categoryName, $categories)) {
                  $categories[] = $categoryName;
                }
              }
              sort($categories);
              foreach ($categories as $categoryOption):
            ?>
              <option value="<?php echo htmlspecialchars(strtolower($categoryOption)); ?>"><?php echo htmlspecialchars($categoryOption); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-striped table-hover dispatcher-table" id="dashboardItemsTable">
          <thead>
            <tr>
              <th>Item Name</th>
              <th>Category</th>
              <th class="text-center">Available Stock</th>
              <th class="text-center">Used</th>
              <th class="text-center">Unit</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($grouped_items)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No available items for dispatch.</td>
              </tr>
            <?php else: ?>
              <?php foreach($grouped_items as $prod): ?>
                <?php $available = max(0, (int)$prod['quantity'] - (int)$prod['used']); ?>
                <tr>
                  <td><?php echo remove_junk(first_character($prod['name'])); ?></td>
                  <td><?php echo remove_junk(first_character($prod['categorie'])); ?></td>
                  <td class="text-center"><?php echo $available; ?> <?php echo remove_junk($prod['unit']); ?></td>
                  <td class="text-center"><?php echo (int)$prod['used']; ?> <?php echo remove_junk($prod['unit']); ?></td>
                  <td class="text-center"><?php echo remove_junk($prod['unit']); ?></td>
                  <td class="text-center dispatcher-actions">
                    <a href="add_sale.php?group_name=<?php echo rawurlencode($prod['name']); ?>&group_unit=<?php echo rawurlencode($prod['unit']); ?>&group_cat_id=<?php echo (int)$prod['categorie_id']; ?>" class="btn btn-sm btn-primary">Select</a>
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
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('dashboardItemSearch');
    const table = document.getElementById('dashboardItemsTable');

    if (!searchInput || !table) return;

    const categoryFilter = document.getElementById('dashboardCategoryFilter');

    const filterRows = () => {
      const searchValue = searchInput.value.toLowerCase().trim();
      const categoryValue = categoryFilter.value.toLowerCase().trim();
      const rows = table.tBodies[0].rows;

      Array.from(rows).forEach(row => {
        const cellsText = Array.from(row.cells).slice(0, 5).map(cell => cell.textContent.toLowerCase()).join(' ');
        const matchesSearch = searchValue === '' || cellsText.includes(searchValue);
        const categoryText = row.cells[1].textContent.toLowerCase();
        const matchesCategory = categoryValue === '' || categoryText === categoryValue;
        row.style.display = matchesSearch && matchesCategory ? '' : 'none';
      });
    };

    searchInput.addEventListener('input', filterRows);
    categoryFilter.addEventListener('change', filterRows);
  });
</script>
<?php include_once('layouts/footer.php'); ?>
