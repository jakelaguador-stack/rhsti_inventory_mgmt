<?php
  // 1. Simulan ang output buffering para walang anumang warning na lumabas bago ang headers
  ob_start();

  require_once('includes/load.php');
  page_require_level(2);

  $products = array();
  
  // Ligtas na pag-check kung gumagana at may ibinabalik na tamang array ang function
  if (function_exists('join_product_table')) {
    $fetched_products = join_product_table();
    if (is_array($fetched_products)) {
        $products = $fetched_products;
    }
  }

  // Kung walang laman o nag-fail ang function, gamitin ang fallback query
  if (empty($products)) {
    global $db;
    // Idinagdag sa SELECT ang p.used (kung mayroon mang column na ganito sa iyong DB)
    $sql = "SELECT p.id, p.name, p.quantity, p.used, '' AS unit, p.buy_price, p.media_id, p.date, c.name AS categorie, m.file_name AS image ";
    $sql .= "FROM products p ";
    $sql .= "LEFT JOIN categories c ON c.id = p.categorie_id ";
    $sql .= "LEFT JOIN media m ON m.id = p.media_id ";
    $sql .= "ORDER BY p.id ASC";
    $result = $db->query($sql);
    $products = array();
    if ($result) {
      while ($row = $db->fetch_assoc($result)) {
        $products[] = $row;
      }
    }
  }

  // Magcompute ng kabuuang bilang para sa buong report summary
  $total_available = 0;
  $total_used = 0;
  foreach($products as $product) {
      $total_available += (int)$product['quantity'];
      $total_used += isset($product['used']) ? (int)$product['used'] : 0;
  }

  // Linisin ang buffer para burahin ang anumang hidden PHP warnings/errors na sumingit sa itaas
  ob_clean();

  $filename = 'product_list_report_' . date('Ymd_His') . '.doc';

  header('Content-Type: application/vnd.ms-word; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  header('Expires: 0');
?>
<!doctype html>
<html lang="en-US">
 <head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title>Items List Report</title>
   <style>
     body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; color: #333; margin: 20px; }
     .date-header { font-size: 11pt; color: #777; margin-bottom: 25px; }
     
     .report-title-container { margin-bottom: 15px; }
     .report-title { font-size: 16pt; color: #6f42c1; font-weight: bold; margin: 0 0 5px 0; }
     .report-subtitle { font-size: 10pt; color: #666; margin: 0 0 15px 0; }
     
     .summary-container { margin-bottom: 20px; }
     .badge-total { 
       background-color: #e7f0ff; 
       color: #0d6efd; 
       font-weight: bold; 
       padding: 5px 12px; 
       border-radius: 4px; 
       display: inline-block;
       font-size: 10pt;
       margin-right: 10px;
     }
     .badge-available { 
       background-color: #e6f4ea; 
       color: #137333; 
       font-weight: bold; 
       padding: 5px 12px; 
       border-radius: 4px; 
       display: inline-block;
       font-size: 10pt;
       margin-right: 10px;
     }
     .badge-used { 
       background-color: #fce8e6; 
       color: #c5221f; 
       font-weight: bold; 
       padding: 5px 12px; 
       border-radius: 4px; 
       display: inline-block;
       font-size: 10pt;
     }
     
     table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 10px; }
     th, td { padding: 10px 8px; vertical-align: middle; text-align: left; }
     
     th { 
       color: #888; 
       font-weight: bold; 
       text-transform: uppercase; 
       font-size: 8pt;
       border-top: 1px solid #dee2e6;
       border-bottom: 2px solid #dee2e6;
       background-color: #ffffff;
     }
     td { border-bottom: 1px solid #f0f0f0; color: #212529; }
     .text-center { text-align: center; }
     .text-success { color: #137333; font-weight: bold; }
     .text-danger { color: #c5221f; font-weight: bold; }
     .product-img { width: 50px; height: auto; max-height: 50px; border-radius: 4px; }
   </style>
 </head>
 <body>

   <div class="date-header">
     <?php echo date('F d, Y, g:i a'); ?>
   </div>

   <div class="report-title-container">
     <h1 class="report-title">📦 Items List Report</h1>
     <p class="report-subtitle">A report of all items with quantities, units, categories, prices, and images.</p>
   </div>

   <div class="summary-container">
     <div class="badge-total">
       Total Products: <?php echo count($products); ?>
     </div>
     <div class="badge-available">
       Total Available Stock: <?php echo $total_available; ?>
     </div>
     <div class="badge-used">
       Total Used Items: <?php echo $total_used; ?>
     </div>
   </div>

   <table border="1" style="border-collapse: collapse; border-color: #f0f0f0;">
     <thead>
       <tr>
         <th style="width: 5%;">#</th>
         <th style="width: 25%;">Product Name</th>
         <th style="width: 15%;">Category</th>
         <th style="width: 10%; text-align: center;">Available Qty</th>
         <th style="width: 10%; text-align: center;">Used Qty</th>
         <th style="width: 8%;">Unit</th>
         <th style="width: 12%;">Buy Price</th>
         <th style="width: 10%; text-align: center;">Image</th>
         <th style="width: 15%;">Date</th>
       </tr>
     </thead>
     <tbody>
       <?php $count = 1; foreach($products as $product): ?>
         <tr>
           <td class="text-center"><?php echo $count++; ?></td>
           <td style="font-weight: bold;"><?php echo htmlspecialchars(remove_junk($product['name']), ENT_QUOTES, 'UTF-8'); ?></td>
           <td><?php echo htmlspecialchars(remove_junk($product['categorie']), ENT_QUOTES, 'UTF-8'); ?></td>
           
           <td class="text-center text-success"><?php echo (int)$product['quantity']; ?></td>
           
           <td class="text-center text-danger"><?php echo isset($product['used']) ? (int)$product['used'] : 0; ?></td>
           
           <td><?php echo htmlspecialchars(remove_junk($product['unit']), ENT_QUOTES, 'UTF-8'); ?></td>
           <td>
             <?php 
               $price = (float)$product['buy_price'];
               echo "PHP " . number_format($price, 2); 
             ?>
           </td>
           <td class="text-center">
             <?php 
               if(!empty($product['image'])): 
                 $img_url = "http://" . $_SERVER['HTTP_HOST'] . "/InventorySystem_PHP/uploads/products/" . $product['image'];
             ?>
               <img class="product-img" src="<?php echo $img_url; ?>" width="50" alt="Product Image">
             <?php else: ?>
               <span style="color: #ccc;">-</span>
             <?php endif; ?>
           </td>
           <td>
             <?php echo date('F d, Y, g:i:s a', strtotime($product['date'])); ?>
           </td>
         </tr>
       <?php endforeach; ?>
     </tbody>
   </table>

 </body>
</html>
<?php 
  // I-flush o ilabas na ang malinis na HTML patungo sa Word file download
  ob_end_flush();
  exit; 
?>