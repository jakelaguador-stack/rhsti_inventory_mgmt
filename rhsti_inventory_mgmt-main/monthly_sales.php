<?php
  $page_title = 'Monthly Requests';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(3);
?>
<?php
 $year = date('Y');
 $requests = monthlyRequests($year);

 // Compute year-to-date used quantity and revenue summary
 $yearly_total_qty = 0;
 $yearly_total_revenue = 0;
 foreach ($requests as $request) {
    $yearly_total_qty += (int)$request['qty'];
    $yearly_total_revenue += (float)$request['total_saleing_price'];
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
  .stat-card {
    border: none;
    border-radius: 12px;
    padding: 1.25rem;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(162, 171, 187, 0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
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
  .badge-qty {
    background-color: #f0fdf4;
    color: #166534;
    padding: 0.35rem 0.7rem;
    border-radius: 6px;
    font-weight: 600;
  }
  .price-text {
    font-weight: 700;
    color: #0f172a;
  }
  .month-badge {
    background-color: #f1f5f9;
    color: #334155;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }
</style>

<div class="container-fluid py-5" style="max-width: 1400px;">
  <div class="row mb-4">
    <div class="col-md-6">
      <?php echo display_msg($msg); ?>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="stat-card">
        <div class="stat-icon" style="background-color: #f5f3ff; color: #7c3aed;">
          <i class="fas fa-calendar-days"></i>
        </div>
        <div>
          <span class="text-muted small d-block fw-medium">Active Fiscal Year</span>
          <h5 class="fw-bold mb-0 text-dark">Year <?php echo $year; ?></h5>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card">
        <div class="stat-icon" style="background-color: #f0fdf4; color: #166534;">
          <i class="fas fa-cubes"></i>
        </div>
        <div>
          <span class="text-muted small d-block fw-medium">Yearly Items Requested</span>
          <h5 class="fw-bold mb-0 text-dark"><?php echo number_format($yearly_total_qty); ?> pcs</h5>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card">
        <div class="stat-icon" style="background-color: #fef3c7; color: #d97706;">
          <i class="fas fa-chart-pie"></i>
        </div>
        <div>
          <span class="text-muted small d-block fw-medium">Total Accumulated Revenue</span>
          <h5 class="fw-bold mb-0 text-warning" style="color: #b45309 !important;">₱<?php echo number_format($yearly_total_revenue, 2); ?></h5>
        </div>
      </div>
    </div>
  </div>

  <div class="card custom-card">
    <div class="card-header bg-white py-4 px-4 border-0 d-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-0 fw-bold text-dark d-flex align-items-center">
          <i class="fas fa-chart-line text-purple me-2" style="color: #7c3aed;"></i> Monthly Request Overview
        </h4>
        <p class="text-muted small mb-0">Performance breakdown grouped by products and months</p>
      </div>
      <span class="badge rounded-pill bg-light text-dark border px-3 py-2 fw-semibold">
        Total Groups: <?php echo count($requests); ?>
      </span>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-modern table-hover align-middle mb-0">
          <thead>
            <tr>
              <th class="text-center py-3" style="width: 80px;">#</th>
              <th class="py-3">Product Name</th>
              <th class="text-center py-3" style="width: 20%;">Total Quantity Requested</th>
              <th class="text-center py-3" style="width: 20%;">Monthly Value</th>
              <th class="text-center py-3" style="width: 20%;">Month/Year</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($requests)): ?>
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <img src="https://cdn-icons-png.flaticon.com/512/11624/11624545.png" alt="No Requests" style="width: 80px; opacity: 0.4;" class="mb-3 d-block mx-auto">
                  <h5 class="fw-bold">No Monthly Records Available</h5>
                  <p class="small">Request data will build up here as the year progresses.</p>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($requests as $request): ?>
              <tr>
                <td class="text-center text-muted fw-bold"><?php echo count_id(); ?></td>
                <td>
                  <div class="fw-semibold text-dark"><?php echo remove_junk($request['name']); ?></div>
                </td>
                <td class="text-center">
                  <span class="badge-qty"><i class="fas fa-arrow-trend-up me-1"></i> <?php echo (int)$request['qty']; ?></span>
                </td>
                <td class="text-center">
                  <span class="price-text">₱<?php echo number_format((float)$request['total_saleing_price'], 2); ?></span>
                </td>
                <td class="text-center">
                  <span class="month-badge">
                    <i class="far fa-calendar-check text-muted"></i>
                    <?php echo date("F, Y", strtotime($request['date'])); ?>
                  </span>
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

<?php include_once('layouts/footer.php'); ?>