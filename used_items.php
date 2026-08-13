<?php
  $page_title = 'Used Items';
  require_once('includes/load.php');
  page_require_level(2);
  global $db;

  // Query para makuha ang mga gamit at categories
  $sql = "SELECT p.id AS product_id, p.name, s.id AS sale_id, ABS(s.qty) AS used, p.buy_price, p.unit, s.date, c.name AS categorie, c.id AS categorie_id " .
         "FROM sales s " .
         "LEFT JOIN products p ON p.id = s.product_id " .
         "LEFT JOIN categories c ON c.id = p.categorie_id " .
         "WHERE s.qty <> 0 ORDER BY s.date DESC";
  $flattened_rows = find_by_sql($sql);
  
  // Kunin lahat ng categories para sa dropdown
  $categories = find_by_sql("SELECT id, name FROM categories ORDER BY name ASC");
?>
<?php include_once('layouts/header.php'); ?>

<style>
  @media print {
    @page { size: A4 portrait; margin: 10mm; }
    body * { visibility: hidden; }
    #printable-report, #printable-report * { visibility: visible; }
    #printable-report { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
    .remark-input { border: none !important; width: 100%; background: transparent; }
  }

  .table { width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; }
  .table thead th { background-color: #d9e1f2; border: 1px solid #000; text-align: center; padding: 8px; }
  .table td { border: 1px solid #000; padding: 6px; }
  .remark-input { width: 100%; padding: 4px; border: 1px solid #ccc; }
  
  /* Styling para sa search at filter */
  .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; }
  .search-input { padding: 8px; width: 300px; border: 1px solid #ccc; }
  .cat-select { padding: 8px; width: 200px; border: 1px solid #ccc; }
</style>

<div class="row no-print">
  <div class="col-md-12">
    <!-- Filter Section -->
    <div class="filter-bar">
      <input type="text" id="searchInput" class="search-input" placeholder="Search product name..." onkeyup="filterTable()">
      <select id="catSelect" class="cat-select" onchange="filterTable()">
        <option value="">All Categories</option>
        <?php foreach($categories as $cat): ?>
          <option value="<?php echo $cat['name']; ?>"><?php echo $cat['name']; ?></option>
        <?php endforeach; ?>
      </select>
      <button onclick="window.print()" class="btn btn-primary"><span class="glyphicon glyphicon-print"></span> Print</button>
    </div>
  </div>
</div>

<div id="printable-report">
  <div style="text-align:center; margin-bottom: 20px;">
    <h4>The Ripple of Hope Skills and Technology Institute Inc.</h4>
    <h5>FACILITIES INVENTORY RECORD (USED ITEMS)</h5>
  </div>

  <table class="table table-bordered" id="usedTable">
    <thead>
      <tr>
        <th>DATE & TIME</th>
        <th>DESCRIPTION OF ITEM</th>
        <th>CATEGORY</th>
        <th>QTY</th>
        <th>UNIT</th>
        <th>UNIT COST</th>
        <th>TOTAL COST</th>
        <th>REMARKS</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($flattened_rows as $row): 
            $total = (float)$row['used'] * (float)$row['buy_price'];
      ?>
      <tr>
        <td class="text-center" style="white-space: nowrap;"><?php echo date('M d, Y, h:i A', strtotime($row['date'])); ?></td>
        <td><?php echo remove_junk($row['name']); ?></td>
        <td class="category-cell"><?php echo remove_junk($row['categorie']); ?></td>
        <td class="text-center"><?php echo (int)$row['used']; ?></td>
        <td class="text-center"><?php echo remove_junk($row['unit']); ?></td>
        <td class="text-right"><?php echo number_format((float)$row['buy_price'], 2); ?></td>
        <td class="text-right"><?php echo number_format($total, 2); ?></td>
        <td><input type="text" class="remark-input" data-id="<?php echo $row['sale_id']; ?>" oninput="saveRemark(this)"></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  // Filter Function
  function filterTable() {
    var search = document.getElementById('searchInput').value.toLowerCase();
    var cat = document.getElementById('catSelect').value.toLowerCase();
    var table = document.getElementById('usedTable');
    var tr = table.getElementsByTagName('tr');

    for (var i = 1; i < tr.length; i++) {
      var name = tr[i].getElementsByTagName('td')[1].textContent.toLowerCase();
      var category = tr[i].getElementsByClassName('category-cell')[0].textContent.toLowerCase();
      
      if (name.indexOf(search) > -1 && (cat === "" || category === cat)) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }

  // Save Remarks
  function saveRemark(input) {
    localStorage.setItem('remark_' + input.getAttribute('data-id'), input.value);
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.remark-input').forEach(function(input) {
      input.value = localStorage.getItem('remark_' + input.getAttribute('data-id')) || "";
    });
  });
</script>

<?php include_once('layouts/footer.php'); ?>