<?php
  // 1. Simulan ang output buffering para walang anumang warning na lumabas bago ang headers
  ob_start();

  require_once('includes/load.php');
  page_require_level(2);

  $products = array();
  $search = isset($_GET['q']) ? trim($_GET['q']) : '';
  $selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
  
  // Always fetch products with category information using proper SQL query
  global $db;
  $sql = "SELECT p.id, p.name, p.quantity, p.used, p.unit, p.buy_price, p.media_id, p.date, c.name AS categorie, m.file_name AS image, p.remarks ";
  $sql .= "FROM products p ";
  $sql .= "LEFT JOIN categories c ON c.id = p.categorie_id ";
  $sql .= "LEFT JOIN media m ON m.id = p.media_id ";
  $sql .= "ORDER BY p.id ASC";
  
  $result = $db->query($sql);
  if ($result) {
    while ($row = $db->fetch_assoc($result)) {
      $products[] = $row;
    }
  }

  if ($search !== '' || $selected_category !== '') {
    $search_l = strtolower($search);
    $products = array_filter($products, function($product) use ($search_l, $selected_category) {
      $name = strtolower((string)($product['name'] ?? ''));
      $cat = strtolower((string)($product['categorie'] ?? ''));
      $unit = strtolower((string)($product['unit'] ?? ''));
      $price = strtolower((string)($product['buy_price'] ?? ''));
      $date = strtolower((string)($product['date'] ?? ''));
      
      $matchesSearch = $search_l === '' || 
                       strpos($name, $search_l) !== false || 
                       strpos($cat, $search_l) !== false || 
                       strpos($unit, $search_l) !== false || 
                       strpos($price, $search_l) !== false || 
                       strpos($date, $search_l) !== false;
                       
      $matchesCategory = $selected_category === '' || strtolower($selected_category) === $cat;
      return $matchesSearch && $matchesCategory;
    });
  }

  // --- 🛠️ DITO NATIN PROPROSESUHIN ANG LOGO PARA SA WORD ---
  $logo_path = __DIR__ . '/uploads/images/logo.jpg';
  $logo_base64_clean = '';
  
  if (file_exists($logo_path)) {
      $logo_data = file_get_contents($logo_path);
      // Kinukuha ang purong base64 nang walang "data:image/png;base64," prefix para sa multipart header
      $logo_base64_clean = base64_encode($logo_data);
  }

  // Linisin ang buffer para burahin ang anumang sumingit na error/warning
  ob_clean();

  $filename = 'facilities_inventory_report_' . date('Ymd_His') . '.doc';

  // Gamitin ang tamang MHTML header para tanggapin ng Word ang file attachments
  header('Content-Type: application/msword');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  header('Expires: 0');

  // Boundary text para paghiwalayin ang HTML at ang Imahe
  $boundary = "===MULTIPART_BOUNDARY_LOGO_REPORT===";

  // Sinasabi sa Word na ituring itong Web Archive Multipart File
  echo "MIME-Version: 1.0\r\n";
  echo "Content-Type: multipart/related; type=\"text/html\"; boundary=\"$boundary\"\r\n\r\n";

  // --- PART 1: ANG HTML CONTENT ---
  echo "--$boundary\r\n";
  echo "Content-Type: text/html; charset=utf-8\r\n";
  echo "Content-Transfer-Encoding: 8bit\r\n\r\n";
?>
<!doctype html>
<html lang="en-US">
 <head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title>Facilities Inventory Record</title>
   <style>
     body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; color: #333; margin: 20px; }
     .header-table { width: 100%; border: none; margin-bottom: 20px; }
     .header-logo { width: 90px; text-align: right; vertical-align: middle; }
     .header-text { text-align: center; vertical-align: middle; font-family: 'Times New Roman', Times, serif; }
     .institution-name { font-size: 14pt; font-weight: bold; color: #000; margin: 0; }
     .institution-details { font-size: 9pt; font-style: italic; color: #333; margin: 2px 0; }
     .report-title { font-size: 13pt; font-weight: bold; text-align: center; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase; }
     .report-sy { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 25px; }
     table.inventory-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
     table.inventory-table th, table.inventory-table td { border: 1px solid #4f81bd; padding: 6px 8px; vertical-align: middle; }
     table.inventory-table th { background-color: #b4c6e7; color: #000000; font-weight: bold; text-align: center; font-size: 9.5pt; }
     .text-center { text-align: center; }
     .text-right { text-align: right; }
     .text-left { text-align: left; }
   </style>
 </head>
 <body>

   <!-- Institutional Header Section -->
   <table class="header-table">
     <tr>
       <td class="header-logo">
         <!-- Gagamit ng Content-ID (cid:) para basahin ang naka-attach na imahe sa ibaba -->
         <img src="cid:school_logo_image" width="85" height="85" alt="Logo">
       </td>
       <td class="header-text">
         <div class="institution-name">The Ripple of Hope Skills and Technology Institute Inc.</div>
         <div class="institution-details">SYMAR BLDG., VINZONS AVE., BRGY VII, DAET, CAMARINES NORTE</div>
         <div class="institution-details">Sec. Reg. No. CN201701232</div>
         <div class="institution-details">SCHOOL I.D. NO. 408869</div>
       </td>
     </tr>
   </table>

   <!-- Title / School Year -->
   <div class="report-title">Facilities Inventory Record</div>
   <div class="report-sy">SY <?php echo date('Y') . ' - ' . (date('Y') + 1); ?></div>

   <!-- Inventory Spreadsheet Table -->
   <table class="inventory-table">
     <thead>
       <tr>
         <th style="width: 12%;">DATE ACQUIRED</th>
         <th style="width: 30%;">DESCRIPTION OF ITEM</th>
         <th style="width: 7%;">QTY</th>
         <th style="width: 8%;">UNIT</th>
         <th style="width: 12%;">UNIT COST</th>
         <th style="width: 13%;">TOTAL COST</th>
         <th style="width: 18%;">CATEGORY</th>
         <th style="width: 15%;">REMARKS</th>
       </tr>
     </thead>
     <tbody>
       <?php if (!empty($products)): ?>
         <?php foreach($products as $product): 
           $qty = (int)($product['quantity'] ?? 0);
           $unit_cost = (float)($product['buy_price'] ?? 0);
           $total_cost = $qty * $unit_cost;
           $product_date = !empty($product['date']) ? strtotime($product['date']) : time();
         ?>
           <tr>
             <td class="text-center"><?php echo date('m/d/Y', $product_date); ?></td>
             <td class="text-left" style="text-transform: uppercase;"><?php echo htmlspecialchars(remove_junk($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
             <td class="text-center"><?php echo $qty; ?></td>
             <td class="text-center"><?php echo !empty($product['unit']) ? htmlspecialchars(remove_junk($product['unit']), ENT_QUOTES, 'UTF-8') : 'pc'; ?></td>
             <td class="text-right"><?php echo number_format($unit_cost, 2); ?></td>
             <td class="text-right"><?php echo number_format($total_cost, 2); ?></td>
             <td class="text-left"><?php echo htmlspecialchars(remove_junk($product['categorie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
             <td class="text-left"><?php echo htmlspecialchars(remove_junk($product['remarks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
           </tr>
         <?php endforeach; ?>
       <?php else: ?>
         <tr>
           <td colspan="8" class="text-center" style="color: #999; padding: 20px;">No inventory items found.</td>
         </tr>
       <?php endif; ?>
     </tbody>
   </table>

 </body>
</html>
<?php 
  echo "\r\n--$boundary\r\n";

  // --- PART 2: DITO IPAPASOK ANG TOTOONG IMAHE SA LOOB NG MS WORD ---
  if (!empty($logo_base64_clean)) {
      echo "Content-Type: image/jpeg\r\n";
      echo "Content-Transfer-Encoding: base64\r\n";
      echo "Content-ID: <school_logo_image>\r\n";
      echo "Content-Location: cid:school_logo_image\r\n\r\n";
      echo chunk_split($logo_base64_clean) . "\r\n";
      echo "--$boundary--\r\n";
  } else {
      // Ensure the MIME boundary is closed even if no logo is attached
      echo "Content-Type: text/plain; charset=utf-8\r\n\r\n";
      echo "No logo attached.\r\n";
      echo "--$boundary--\r\n";
  }

  ob_end_flush();
  exit;
?>