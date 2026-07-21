<?php
  $page_title = 'Items Report';
  require_once('includes/load.php');
  // Check what level user has permission to view this page
  page_require_level(2);
?>
<?php
$products = join_product_table();

// Mag-initialize ng counters para sa summary badges
$total_available = 0;
$total_requested = 0;

foreach($products as $product) {
    // I-assume natin na ang 'quantity' ay ang kasalukuyang available stock
    $total_available += (int)$product['quantity'];
    
    // Kung may column ka na 'used' o 'sales_quantity', palitan lang ito.
    // Kung wala pa, pansamantalang 0 muna ito o baguhin base sa iyong DB column name.
    $total_requested += isset($product['used']) ? (int)$product['used'] : 0; 
}
?>
<?php include_once('layouts/header.php'); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  body {
    background-color: #f8fafc;
  }
  .custom-card {
    border: none;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(162, 171, 187, 0.15);
    overflow: hidden;
  }
  .table-modern th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    color: #64748b;
    background-color: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
  }
  .table-modern td {
    padding: 1rem 0.75rem;
    color: #334155;
    font-size: 0.9rem;
    border-bottom: 1px solid #f1f5f9;
  }
  .badge-summary {
    background-color: #eef2ff;
    color: #4338ca;
    padding: 0.5rem 0.9rem;
    border-radius: 9999px;
    font-weight: 700;
  }
  .badge-available {
    background-color: #e6f4ea;
    color: #137333;
    padding: 0.5rem 0.9rem;
    border-radius: 9999px;
    font-weight: 700;
  }
  .badge-used {
    background-color: #fce8e6;
    color: #c5221f;
    padding: 0.5rem 0.9rem;
    border-radius: 9999px;
    font-weight: 700;
  }
  .price-text {
    font-weight: 700;
    color: #0f172a;
  }
  .date-text {
    color: #64748b;
    font-size: 0.85rem;
  }
</style>

<div class="container-fluid py-5" style="max-width: 1400px;">
  <div class="row mb-4">
    <div class="col-md-6">
      <?php echo display_msg($msg); ?>
    </div>
  </div>

  <div class="card custom-card">
    <div class="card-header bg-white py-4 px-4 border-0 d-flex align-items-center justify-content-between flex-wrap g-3">
      <div>
        <h4 class="mb-0 fw-bold text-dark d-flex align-items-center">
          <i class="fas fa-boxes text-purple me-2" style="color: #7c3aed;"></i> Item List Report
        </h4>
        <p class="text-muted small mb-0">A report of all items with quantities, units, categories, prices, and images.</p>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge-summary">
          Total Products: <?php echo count($products); ?>
        </span>
        <span class="badge-available">
          Available Stock: <?php echo $total_available; ?>
        </span>
        <span class="badge-used">
          Requested Items: <?php echo $total_requested; ?>
        </span>
        <a href="product_report_word.php" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center" download>
          <i class="fas fa-file-word me-2"></i> Download Word
        </a>
        <a href="product_report_pdf.php" target="_blank" class="btn btn-danger px-4 py-2 rounded-3 shadow-sm d-flex align-items-center" download>
          <i class="fas fa-file-pdf me-2"></i> Download PDF
        </a>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-modern table-hover align-middle mb-0">
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th>Item Name</th>
              <th>Category</th>
              <th class="text-center">Available Qty</th>
              <th class="text-center">Requested Qty</th>
              <th class="text-center">Unit</th>
              <th class="text-center">Buy Price</th>
              <th class="text-center">Image</th>
              <th class="text-center">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($products)): ?>
              <tr>
                <td colspan="9" class="text-center">No products found.</td>
              </tr>
            <?php else: ?>
              <?php $count = 1; ?>
              <?php foreach($products as $product): ?>
                <tr>
                  <td class="text-center"><?php echo $count++; ?></td>
                  <td><?php echo remove_junk(first_character($product['name'])); ?></td>
                  <td><?php echo remove_junk(first_character($product['categorie'])); ?></td>
                  <td class="text-center fw-bold text-success"><?php echo (int)$product['quantity']; ?></td>
                  <td class="text-center fw-bold text-danger"><?php echo isset($product['used']) ? (int)$product['used'] : 0; ?></td>
                  <td class="text-center"><?php echo remove_junk($product['unit']); ?></td>
                  <td class="text-center price-text">PHP <?php echo number_format((float)$product['buy_price'], 2); ?></td>
                  <td class="text-center">
                    <?php if($product['media_id'] === '0' || empty($product['image'])): ?>
                      <img src="uploads/products/no_image.png" alt="No Image" style="height:40px;" />
                    <?php else: ?>
                      <img src="uploads/products/<?php echo $product['image']; ?>" alt="Product Image" style="height:40px;" />
                    <?php endif; ?>
                  </td>
                  <td class="text-center date-text"><?php echo read_date($product['date']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>