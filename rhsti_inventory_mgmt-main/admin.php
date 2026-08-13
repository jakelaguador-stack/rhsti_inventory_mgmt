code <?php

  $page_title = 'Admin Home Page';

  require_once('includes/load.php');

  // Checkin What level user has permission to view this page

  page_require_level(1);

?>

<?php

 $c_categorie     = count_by_id('categories');

 $c_product       = count_by_id('products');

 $c_used_items    = total_used_items();

 $c_user          = count_by_id('users');

 $products_requested   = find_most_used_products('10');

 $recent_products = find_recent_product_added('5');

 $recent_requests    = find_recent_request_added('5');

?>

<?php include_once('layouts/header.php'); ?>



<!-- FontAwesome & Google Font Inter for a clean aesthetic -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">



<style>

  body {

    background-color: #f8fafc;

    font-family: 'Inter', sans-serif;

  }

  

  /* Top Overview Cards Design */

  .premium-dash-card {

    background: #ffffff;

    border-radius: 16px;

    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.03);

    margin-bottom: 24px;

    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    overflow: hidden;

    display: flex;

    align-items: center;

    padding: 20px;

    border: 1px solid #e2e8f0;

  }

  .premium-dash-card:hover {

    transform: translateY(-4px);

    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);

    border-color: #cbd5e1;

  }

  .dash-icon-box {

    width: 56px;

    height: 56px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;

    border-radius: 12px;

    flex-shrink: 0;

  }

  .dash-info-box {

    flex-grow: 1;

    padding-left: 16px;

  }

  .dash-info-box h2 {

    margin: 0;

    font-size: 28px;

    font-weight: 700;

    color: #0f172a;

    letter-spacing: -0.5px;

    line-height: 1.2;

  }

  .dash-info-box p {

    margin: 2px 0 0 0;

    font-size: 0.8rem;

    color: #64748b;

    text-transform: uppercase;

    font-weight: 600;

    letter-spacing: 0.05em;

  }



  /* Academic Theme - Soft Pastel Backgrounds */

  .bg-academic-blue   { background-color: #eff6ff; color: #2563eb; }

  .bg-school-orange   { background-color: #fff7ed; color: #ea580c; }

  .bg-library-green   { background-color: #f0fdf4; color: #16a34a; }

  .bg-faculty-purple  { background-color: #faf5ff; color: #9333ea; }



  /* Premium Content Panels */

  .panel-school {

    background: #ffffff;

    border-radius: 16px;

    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.03);

    border: 1px solid #e2e8f0;

    margin-bottom: 24px;

    overflow: hidden;

  }

  .panel-school .panel-heading {

    background-color: #ffffff !important;

    border-bottom: 1px solid #f1f5f9 !important;

    padding: 1.25rem !important;

  }

  .panel-school .panel-heading strong {

    font-size: 0.95rem;

    color: #0f172a;

    font-weight: 700;

    display: flex;

    align-items: center;

    gap: 8px;

  }

  .panel-school .panel-heading i {

    font-size: 1.2rem;

  }



  /* Tables Makeover */

  .table-school th {

    background-color: #f8fafc;

    color: #475569;

    font-weight: 600;

    text-transform: uppercase;

    font-size: 0.75rem;

    letter-spacing: 0.05em;

    padding: 1rem !important;

    border-bottom: 1px solid #e2e8f0 !important;

  }

  .table-school td {

    padding: 1rem !important;

    vertical-align: middle !important;

    color: #334155;

    font-size: 0.9rem;

    border-bottom: 1px solid #f1f5f9 !important;

  }



  /* Avatar & List Styling */

  .img-avatar-school {

    width: 44px;

    height: 44px;

    object-fit: cover;

    border-radius: 10px;

    margin-right: 12px;

    border: 1px solid #e2e8f0;

    background-color: #f1f5f9;

  }

  .list-school-group .list-group-item {

    border: none;

    border-bottom: 1px solid #f1f5f9;

    padding: 1rem 1.25rem;

    transition: background 0.2s ease;

  }

  .list-school-group .list-group-item:hover {

    background-color: #f8fafc;

  }

  .list-school-group .list-group-item:last-child {

    border-bottom: none;

  }

  

  .badge-premium {

    background-color: #f1f5f9; 

    color: #334155; 

    font-weight: 600; 

    padding: 0.4rem 0.7rem; 

    border-radius: 8px;

    font-size: 0.8rem;

  }

  .price-text-dashboard {

    font-weight: 600; 

    color: #16a34a;

  }

  .label-orange-premium {

    background-color: #fff7ed;

    color: #ea580c;

    font-weight: 600;

    padding: 0.4rem 0.7rem;

    border-radius: 8px;

    font-size: 0.85rem;

    border: 1px solid #ffedd5;

  }

</style>



<div class="container-fluid py-4" style="max-width: 1600px;">

  <!-- Notifications -->

  <div class="row mb-3">

     <div class="col-md-12">

       <?php echo display_msg($msg); ?>

     </div>

  </div>



  <!-- Top Counter Cards -->

  <div class="row">

    

    <div class="col-md-3 col-sm-6">

      <a href="users.php" class="text-decoration-none">

         <div class="premium-dash-card">

           <div class="dash-icon-box bg-academic-blue">

            <i class="fas fa-users"></i>

           </div>

           <div class="dash-info-box">

            <h2><?php echo $c_user['total']; ?></h2>

            <p>Users</p>

           </div>

         </div>

      </a>

    </div>

    

    <div class="col-md-3 col-sm-6">

      <a href="categorie.php" class="text-decoration-none">

         <div class="premium-dash-card">

           <div class="dash-icon-box bg-school-orange">

            <i class="fas fa-tags"></i>

           </div>

           <div class="dash-info-box">

            <h2><?php echo $c_categorie['total']; ?></h2>

            <p>Categories</p>

           </div>

         </div>

      </a>

    </div>

    

    <div class="col-md-3 col-sm-6">

      <a href="product_list.php" class="text-decoration-none">

         <div class="premium-dash-card">

           <div class="dash-icon-box bg-library-green">

            <i class="fas fa-box-open"></i>

           </div>

           <div class="dash-info-box">

            <h2><?php echo $c_product['total']; ?></h2>

            <p>Items</p>

           </div>

         </div>

      </a>

    </div>

    <div class="col-md-3 col-sm-6">
      <a href="product.php" class="text-decoration-none">
         <div class="premium-dash-card">
           <div class="dash-icon-box bg-faculty-purple">
            <i class="fas fa-chart-bar"></i>
           </div>
           <div class="dash-info-box">
            <h2><?php echo number_format((int)$c_used_items['total']); ?></h2>
            <p>Items Used</p>
           </div>
         </div>
      </a>
    </div>
  </div>



  <!-- Data Tables/Lists Grid -->

  <div class="row g-4">



     <!-- Highest Selling Products -->

     <div class="col-lg-4 col-md-6">

       <div class="card panel-school">

         <div class="card-header panel-heading">

            <strong>

              <i class="fas fa-chart-line text-primary"></i> Most Used Items

            </strong>

         </div>

         <div class="card-body p-0">

           <div class="table-responsive">

             <table class="table table-school align-middle mb-0">

              <thead>

                <tr>

                  <th>Title</th>

                  <th class="text-center">Used Qty</th>

                </tr>

              </thead>

              <tbody>

                <?php foreach ($products_requested as $product_sold): ?>

                  <tr>

                    <td class="fw-semibold text-dark"><?php echo remove_junk(first_character($product_sold['name'])); ?></td>

                    <td class="text-center fw-bold text-secondary"><?php echo (int)$product_sold['totalUsed']; ?></td>

                  </tr>

                <?php endforeach; ?>

              </tbody>

             </table>

           </div>

         </div>

       </div>

     </div>

     

     <!-- Latest Item Requests -->

     <div class="col-lg-4 col-md-6">

        <div class="card panel-school">

          <div class="card-header panel-heading">

             <strong>

               <i class="fas fa-receipt text-success"></i> Latest Item Requests

             </strong>

          </div>

          <div class="card-body p-0">

            <div class="table-responsive">

              <table class="table table-school align-middle mb-0">

                <thead>

                  <tr>

                    <th class="text-center" style="width: 50px;">#</th>

                    <th>Product Name</th>

                    <th>Date</th>

                    <th class="text-center">Used Qty</th>

                  </tr>

                </thead>

                <tbody>

                  <?php foreach ($recent_requests as $recent_sale): ?>

                  <tr>

                    <td class="text-center text-muted small fw-bold"><?php echo count_id();?></td>

                    <td>
                      <span class="fw-semibold text-dark">
                       <?php echo remove_junk(first_character($recent_sale['name'])); ?>
                      </span>
                    </td>

                    <td class="small text-secondary"><?php echo date('M d, Y', strtotime($recent_sale['date'])); ?></td>

                    <td class="text-center fw-bold text-secondary"><?php echo (int)$recent_sale['qty']; ?></td>

                  </tr>

                  <?php endforeach; ?>

                </tbody>

              </table>

            </div>

          </div>

        </div>

     </div>

     

     <!-- Recently Added Products -->

     <div class="col-lg-4 col-md-12">

      <div class="card panel-school">

        <div class="card-header panel-heading">

           <strong>

             <i class="fas fa-folder-plus text-warning"></i> Recently Added Products

           </strong>

        </div>

        <div class="card-body p-0">

          <div class="list-group list-school-group mb-0">

            <?php foreach ($recent_products as $recent_product): ?>

              <a class="list-group-item d-flex align-items-center justify-content-between text-decoration-none" href="edit_product.php?id=<?php echo (int)$recent_product['id'];?>">

                  <div class="d-flex align-items-center">

                    <?php if($recent_product['media_id'] === '0'): ?>

                      <img class="img-avatar-school" src="uploads/products/no_image.png" alt="No Image">

                    <?php else: ?>

                      <img class="img-avatar-school" src="uploads/products/<?php echo $recent_product['image'];?>" alt="Product Image" />

                    <?php endif;?>

                    <div>

                      <span class="text-dark fw-bold d-block" style="font-size: 0.9rem;"><?php echo remove_junk(first_character($recent_product['name']));?></span>

                      <small class="text-muted fw-semibold" style="font-size:0.75rem;"><?php echo remove_junk(first_character($recent_product['categorie'])); ?></small>

                    </div>

                  </div>

                  <span class="label label-orange-premium">

                    ₱<?php echo number_format((float)$recent_product['buy_price'], 2); ?>

                  </span>

              </a>

            <?php endforeach; ?>

          </div>

        </div>

      </div>

     </div>



  </div>

</div>



<?php include_once('layouts/footer.php'); ?>