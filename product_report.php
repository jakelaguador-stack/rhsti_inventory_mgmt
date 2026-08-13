<?php
  $page_title = 'Items Report';
  require_once('includes/load.php');
  // Check what level user has permission to view this page
  page_require_level(2);
?>
<?php
$products = join_product_table();
$categories = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Mag-initialize ng counters para sa summary badges
$total_available = 0;
$total_requested = 0;

if (!empty($products)) {
    foreach($products as $product) {
        $category_name = trim((string)$product['categorie']);
        if ($category_name !== '') {
            $categories[$category_name] = $category_name;
        }
    }
    if (!empty($categories)) {
      asort($categories, SORT_NATURAL | SORT_FLAG_CASE);
    }

    if ($search !== '' || $selected_category !== '') {
      $search_l = strtolower($search);
      $products = array_filter($products, function($product) use ($search_l, $selected_category) {
        $name = strtolower((string)($product['name'] ?? ''));
        $cat = strtolower((string)($product['categorie'] ?? ''));
        $unit = strtolower((string)($product['unit'] ?? ''));
        $price = strtolower((string)($product['buy_price'] ?? ''));
        $date = strtolower((string)($product['date'] ?? ''));
        $matchesSearch = $search_l === '' || strpos($name, $search_l) !== false || strpos($cat, $search_l) !== false || strpos($unit, $search_l) !== false || strpos($price, $search_l) !== false || strpos($date, $search_l) !== false;
        $matchesCategory = $selected_category === '' || strtolower($selected_category) === $cat;
        return $matchesSearch && $matchesCategory;
      });
    }

    foreach($products as $product) {
        $total_available += (int)$product['quantity'];
        $total_requested += isset($product['used']) ? (int)$product['used'] : 0; 
    }
}
?>
<?php include_once('layouts/header.php'); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  body {
    background-color: #eef2ff;
  }
  .custom-card {
    border: none;
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 20px 35px rgba(15, 23, 42, 0.06);
    overflow: hidden;
  }
  
  /* Institutional Letterhead Style */
  .institute-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    border-bottom: 2px solid #3b82f6;
    padding-bottom: 20px;
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
    font-size: 1.5rem;
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

  /* Table Custom Stylings */
  .table-modern {
    width: 100%;
    border-collapse: collapse;
    min-width: 1100px;
  }
  .table-modern thead th {
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.78rem;
    letter-spacing: 0.05em;
    color: #1e293b;
    background-color: #b9cde5; 
    border: 1px solid #94a3b8;
    padding: 0.8rem 0.6rem;
    vertical-align: middle;
    white-space: nowrap;
    text-align: center;
  }
  .table-modern thead th.text-left-header {
    text-align: left;
  }
  .table-modern td {
    padding: 0.75rem 0.6rem;
    color: #1e293b;
    font-size: 0.88rem;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    vertical-align: middle;
    text-align: center; 
  }
  .table-modern td.item-desc {
    text-align: left;
  }
  .table-modern tbody tr:nth-child(even) {
    background: #f8fafc;
  }
  .table-modern tbody tr:hover {
    background: #f1f5f9;
  }
  .table-modern td img {
    border-radius: 6px;
    max-height: 40px;
    width: auto;
    object-fit: cover;
  }
  .zoomable-report-image {
    cursor: pointer;
    transition: transform 0.2s ease;
  }
  .zoomable-report-image:hover {
    transform: scale(1.05);
  }
  .badge-summary, .badge-available, .badge-used, .badge-category {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.55rem 1rem;
    border-radius: 9999px;
    font-weight: 700;
    font-size: 0.85rem;
  }
  .report-actions {
    gap: 0.85rem;
    flex-wrap: wrap;
  }
  .report-actions .btn {
    min-width: 155px;
    border-radius: 14px;
  }
  .badge-summary { background-color: #eef2ff; color: #4338ca; }
  .badge-available { background-color: #e6f4ea; color: #137333; }
  .badge-used { background-color: #fce8e6; color: #b42318; }
  
  .badge-category {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    cursor: pointer;
  }
  .price-text {
    font-weight: 600;
    color: #0f172a;
  }
  .date-text {
    color: #475569;
    font-size: 0.85rem;
  }
  .remark-cell, .location-cell {
    min-width: 120px;
    white-space: normal;
  }
  .remark-cell:focus, .location-cell:focus {
    outline: 2px solid #4338ca;
    background: #fff !important;
  }
  .filter-panel input, .filter-panel select {
    min-height: 44px;
    border-radius: 12px;
    border: 1px solid #cbd5e1;
  }

  /* --- PINALAKAS NA ARYENTASYON AT PAGPAPA-GITNA --- */
  @media print {
    @page {
      size: landscape;
      margin: 0.5in;
    }
    
    html, body {
      background: white !important;
      font-family: Arial, sans-serif;
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
    }
    
    header, .sidebar, .container-fluid .row.mb-4, .report-actions, .filter-panel, .btn, input, select, .badge-category {
      display: none !important;
    }
    
    /* Sapilitang itinutulak ang buong wrapper sa center ng pahina */
    .container-fluid {
      padding: 0 !important;
      margin: 0 auto !important;
      width: 100% !important;
      max-width: 100% !important;
      display: block !important;
    }
    
    .card.custom-card {
      box-shadow: none !important;
      background: transparent !important;
      padding: 0 !important;
      margin: 0 auto !important; /* Gitna horizontally */
      width: 95% !important; /* Binigyan ng kaunting space sa gilid para maging pantay ang pagka-sentro */
    }
    
    .card-header, .card-body, .table-responsive {
      padding: 0 !important;
      margin: 0 auto !important;
      width: 100% !important;
    }
    
    /* School Letterhead Alignment */
    .institute-header {
      border-bottom: 3px double #1e3a8a !important;
      margin: 0 auto 20px auto !important;
      display: flex !important;
      justify-content: center !important;
      align-items: center !important;
      text-align: center !important;
      width: 100% !important;
    }
    
    /* Table Stretch and Auto Alignment */
    .table-modern {
      width: 100% !important;
      min-width: 100% !important;
      table-layout: fixed !important;
      margin: 0 auto !important;
    }
    
    .table-modern thead th {
      background-color: #b9cde5 !important; 
      color: #000000 !important;
      border: 1px solid #000000 !important;
      font-size: 0.70rem !important;
      padding: 5px 2px !important;
      text-align: center !important;
      vertical-align: middle !important;
    }
    
    .table-modern thead th.text-left-header {
      text-align: left !important;
    }
    
    .table-modern td {
      color: #000000 !important;
      border: 1px solid #000000 !important;
      font-size: 0.70rem !important;
      padding: 5px 2px !important;
      background: transparent !important;
      word-break: break-word !important;
      text-align: center !important;
      vertical-align: middle !important;
    }
    
    .table-modern td.item-desc {
      text-align: left !important;
    }
    
    .table-modern td img {
      max-height: 25px !important;
      display: block;
      margin: 0 auto !important;
    }
  }
</style>

<div class="container-fluid py-4" style="max-width: 1420px;">
  <div class="row mb-3">
    <div class="col-md-6">
      <?php echo display_msg($msg); ?>
    </div>
  </div>

  <div class="card custom-card">
    <div class="card-header bg-white pt-4 px-4 border-0">
      
      <!-- School Institution Header / Letterhead -->
      <div class="institute-header">
        <img src="uploads/images/logo.jpg" alt="The Ripple of Hope Logo" class="institute-logo">
        <div class="institute-title-block">
          <div class="institute-title">The Ripple of Hope Skills and Technology Institute Inc.</div>
          <div class="institute-sub">SYMAR BLDG., VINZONS AVE., BRGY VII, DAET, CAMARINES NORTE</div>
          <div class="institute-details">Sec. Reg. No. CN201701232 | SCHOOL I.D. NO. 408869</div>
          <h5 class="report-title-badge mb-0 mt-3">FACILITIES INVENTORY RECORD</h5>
          <div class="fw-bold text-secondary small">Report Generation Date: <?php echo date("F d, Y"); ?></div>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-4">
        <div class="d-flex align-items-center gap-2 flex-wrap report-actions w-100 justify-content-md-end">
          <span class="badge-summary">Total Products: <?php echo count($products); ?></span>
          <span class="badge-available">Available Stock: <?php echo $total_available; ?></span>
          <span class="badge-used">Requested Items: <?php echo $total_requested; ?></span>
          <a id="reportWordLink" href="product_report_word.php" class="btn btn-primary px-3 py-2 d-flex align-items-center shadow-sm" download>
            <i class="fas fa-file-word me-2"></i> Download Word
          </a>
          <a id="reportPdfLink" href="product_report_pdf.php" target="_blank" class="btn btn-danger px-3 py-2 d-flex align-items-center shadow-sm">
            <i class="fas fa-file-pdf me-2"></i> View/Print PDF
          </a>
          <button id="reportPrint" type="button" class="btn btn-secondary px-3 py-2 d-flex align-items-center shadow-sm">
            <i class="fas fa-print me-2"></i> Print Report
          </button>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="px-4 pt-2">
        <div class="row g-3 mb-3 filter-panel">
          <div class="col-md-6">
            <input id="reportSearch" name="q" type="search" class="form-control" placeholder="Search item name, category, unit, price..." value="<?php echo htmlspecialchars($search); ?>" />
          </div>
          <div class="col-md-4">
            <select id="categoryFilter" name="category" class="form-select">
              <option value="">All Categories</option>
              <?php if (!empty($categories)): ?>
                <?php foreach($categories as $cat): ?>
                  <option value="<?php echo htmlspecialchars($cat); ?>" <?php if($selected_category === $cat) echo 'selected'; ?>><?php echo remove_junk(first_character($cat)); ?></option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="table-responsive px-4 pb-4">
        <table class="table table-modern table-hover align-middle mb-0">
          <thead>
            <tr>
              <th style="width: 3%;">#</th>
              <th style="width: 12%;">Date Acquired</th>
              <th class="text-left-header" style="width: 20%;">Description of Item</th>
              <th style="width: 5%;">Qty</th>
              <th style="width: 5%;">Unit</th>
              <th style="width: 9%;">Unit Cost</th>
              <th style="width: 10%;">Total Cost</th>
              <th style="width: 6%;">Image</th>
              <th style="width: 10%;">Serial Number</th>
              <th style="width: 8%;">Receipt No.</th>
              <th style="width: 12%;">Remarks</th>
              <th style="width: 12%;">Location</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($products)): ?>
              <tr>
                <td colspan="12" class="text-center text-muted py-4">No records found.</td>
              </tr>
            <?php else: ?>
              <?php $count = 1; ?>
              <?php foreach($products as $product): ?>
                <tr>
                  <td><?php echo $count++; ?></td>
                  <td class="date-text"><?php echo read_date($product['date']); ?></td>
                  <td class="fw-bold item-desc"><?php echo remove_junk(first_character($product['name'])); ?></td>
                  <td class="fw-bold"><?php echo (int)$product['quantity']; ?></td>
                  <td><?php echo remove_junk($product['unit']); ?></td>
                  <td class="price-text">PHP <?php echo number_format((float)$product['buy_price'], 2); ?></td>
                  <td class="price-text text-primary">PHP <?php echo number_format((float)$product['buy_price'] * (int)$product['quantity'], 2); ?></td>
                  <td>
                    <?php
                      $image_file = !empty($product['image']) ? basename($product['image']) : '';
                      $image_path = '';
                      if (!empty($product['media_id']) && $product['media_id'] !== '0' && $image_file !== '') {
                        $possible = __DIR__ . '/uploads/products/' . $image_file;
                        if (file_exists($possible)) {
                          $image_path = 'uploads/products/' . $image_file;
                        }
                      }
                    ?>
                    <?php if (empty($image_path)): ?>
                      <img src="uploads/products/no_image.png" alt="No Image" class="img-thumbnail" style="height:35px; width:35px; object-fit:cover;" onerror="this.src='https://i.imgur.com/hf4sCsa.png';" />
                    <?php else: ?>
                      <img src="<?php echo htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8'); ?>" alt="Product Image" class="img-thumbnail zoomable-report-image" data-full-src="<?php echo htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8'); ?>" style="height:35px; width:35px; object-fit:cover;" />
                    <?php endif; ?>
                  </td>
                  <td><?php echo !empty($product['serial_number']) ? remove_junk($product['serial_number']) : '-'; ?></td>
                  <td><?php echo !empty($product['receipt_number']) ? remove_junk($product['receipt_number']) : '-'; ?></td>
                  <td class="remark-cell" data-product-id="<?php echo (int)$product['id']; ?>" contenteditable="true"><?php echo htmlspecialchars(remove_junk($product['remarks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="location-cell"><?php echo remove_junk(first_character($product['categorie'])); ?></td>
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
  var searchInput = document.getElementById('reportSearch');
  var categoryFilter = document.getElementById('categoryFilter');
  var rows = document.querySelectorAll('.table-modern tbody tr');
  if (!searchInput || !categoryFilter || rows.length === 0) return;

  function applyFilter() {
    var query = searchInput.value.toLowerCase().trim();
    var category = categoryFilter.value.toLowerCase();

    rows.forEach(function(row) {
      var cells = row.querySelectorAll('td');
      var text = '';
      cells.forEach(function(cell) {
        text += ' ' + cell.textContent.toLowerCase();
      });

      var matchesText = text.indexOf(query) !== -1;
      var matchesCategory = category === '' || text.indexOf(category) !== -1;
      row.style.display = matchesText && matchesCategory ? '' : 'none';
    });
  }

  searchInput.addEventListener('input', applyFilter);
  categoryFilter.addEventListener('change', applyFilter);

  function updateExportLinks() {
    var query = encodeURIComponent(searchInput.value.trim());
    var category = encodeURIComponent(categoryFilter.value.trim());
    var params = [];
    if (query) params.push('q=' + query);
    if (category) params.push('category=' + category);
    var queryString = params.length ? '?' + params.join('&') : '';
    var wordLink = document.getElementById('reportWordLink');
    var pdfLink = document.getElementById('reportPdfLink');
    if (wordLink) wordLink.href = 'product_report_word.php' + queryString;
    if (pdfLink) pdfLink.href = 'product_report_pdf.php' + queryString;
  }

  updateExportLinks();
  searchInput.addEventListener('input', updateExportLinks);
  categoryFilter.addEventListener('change', updateExportLinks);

  var printButton = document.getElementById('reportPrint');
  if (printButton) {
    printButton.addEventListener('click', function() {
      window.print();
    });
  }

  function notifyRemark(message, success) {
    if (window.console) {
      console.log('Product remark update:', message);
    }
  }

  document.body.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && document.activeElement && document.activeElement.classList && document.activeElement.classList.contains('remark-cell')) {
      e.preventDefault();
      document.activeElement.blur();
    }
  });

  document.body.addEventListener('blur', function(e) {
    var el = e.target;
    if (!el || !el.classList) return;
    if (el.classList.contains('remark-cell')) {
      var productId = el.getAttribute('data-product-id');
      if (!productId) return;
      var newRemark = el.innerText.trim();
      fetch('update_product_remark.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId) + '&remark=' + encodeURIComponent(newRemark)
      }).then(function(resp) { return resp.json(); }).then(function(json) {
        if (json && json.status === 'success') {
          notifyRemark('Saved', true);
        } else {
          notifyRemark(json && json.message ? json.message : 'Save failed', false);
        }
      }).catch(function(err) {
        notifyRemark('Network error', false);
      });
    }
  }, true);

  var zoomModal = document.createElement('div');
  zoomModal.id = 'report-zoom-modal';
  zoomModal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.85);display:none;align-items:center;justify-content:center;z-index:1100;cursor:zoom-out;padding:20px;';
  zoomModal.innerHTML = '<img id="report-zoom-image" src="" alt="Zoomed Product" style="max-width:100%;max-height:100%;border-radius:18px;box-shadow:0 30px 60px rgba(0,0,0,0.3);object-fit:contain;" />';
  document.body.appendChild(zoomModal);

  var reportZoomImage = document.getElementById('report-zoom-image');
  zoomModal.addEventListener('click', function() {
    zoomModal.style.display = 'none';
    reportZoomImage.src = '';
  });

  document.querySelectorAll('.zoomable-report-image').forEach(function(img) {
    img.addEventListener('click', function(event) {
      event.stopPropagation();
      var fullSrc = img.getAttribute('data-full-src') || img.src;
      reportZoomImage.src = fullSrc;
      zoomModal.style.display = 'flex';
    });
  });
});
</script>

<?php include_once('layouts/footer.php'); ?>