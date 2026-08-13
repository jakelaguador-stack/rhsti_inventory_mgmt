<?php
  $page_title = 'Items List Report PDF';
  require_once('includes/load.php');
  page_require_level(2);

  // 1. Kunin ang eksaktong arrangement mula sa database gaya ng nasa web UI mo
  $products = array();
  if (function_exists('join_product_table')) {
    $products = join_product_table();
  }

  if (empty($products)) {
    global $db;
    // Idinagdag ang p.used sa select query bilang fallback
    $sql = "SELECT p.id, p.name, p.quantity, p.used, '' AS unit, p.buy_price, p.media_id, p.date, c.name AS categorie, m.file_name AS image ";
    $sql .= "FROM products p ";
    $sql .= "LEFT JOIN categories c ON c.id = p.categorie_id ";
    $sql .= "LEFT JOIN media m ON m.id = p.media_id ";
    $sql .= "ORDER BY p.id ASC"; 
    $result = $db->query($sql);
    $products = array();
    while ($row = $db->fetch_assoc($result)) {
      $products[] = $row;
    }
  }

  // Magcompute ng kabuuang bilang para sa header badge ng PDF
  $total_available = 0;
  $total_used = 0;
  foreach($products as $product) {
      $total_available += (int)$product['quantity'];
      $total_used += isset($product['used']) ? (int)$product['used'] : 0;
  }

  $filename = 'product_list_report_' . date('Ymd_His') . '.pdf';

  // Helper functions para sa text escaping
  function pdf_escape($text) {
    $text = (string) $text;
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('(', '\\(', $text);
    $text = str_replace(')', '\\)', $text);
    return $text;
  }

  // Mas malawak na limit para hindi maputol ang teksto
  function pdf_text_limit($text, $max_length = 25) {
    $text = trim((string) $text);
    if (strlen($text) <= $max_length) {
      return $text;
    }
    return substr($text, 0, $max_length - 3) . '...';
  }

  // Function para sa pag-draw ng bawat kahon/cell ng table
  function pdf_draw_cell(&$content, $x, $y, $width, $height, $text, $font = 'F1', $font_size = 7.0, $align = 'left', $padding = 4, $fill_color = null, $stroke = true) {
    if ($fill_color !== null) {
      $content .= "{$fill_color} rg\n";
      $content .= "{$x} {$y} {$width} {$height} re f\n";
    }

    // Border Color: Light gray para malinis tingnan kagaya sa web table mo
    $content .= "0.85 0.85 0.85 RG\n";
    if ($stroke) {
      $content .= "{$x} {$y} {$width} {$height} re S\n";
    }

    $safe_text = pdf_escape((string) $text);
    $text_length = strlen($safe_text) * ($font_size * 0.45); 
    $text_x = $x + $padding;

    if ($align === 'center') {
      $text_x = $x + max($padding, ($width - $text_length) / 2);
    } elseif ($align === 'right') {
      $text_x = $x + max($padding, $width - $text_length - $padding);
    }

    // Kulay ng text (0 0 0 = Itim)
    $content .= "0 0 0 rg\n";
    $content .= "BT\n/$font {$font_size} Tf\n{$text_x} " . ($y + (($height - $font_size) / 2) - 1) . " Td\n(" . $safe_text . ") Tj\nET\n";
  }

  // RECALIBRATED COLUMNS: Inayos ang mga 'width' at 'x' coordinates para magkasya ang 9 columns sa 572 total width limit
  $columns = array(
    array('title' => '#', 'width' => 22, 'x' => 20),
    array('title' => 'PRODUCT NAME', 'width' => 125, 'x' => 42),
    array('title' => 'CATEGORY', 'width' => 85, 'x' => 167),
    array('title' => 'AVAIL QTY', 'width' => 50, 'x' => 252), // Dating QUANTITY
    array('title' => 'USED QTY', 'width' => 50, 'x' => 302),  // Bagong Column para sa Used
    array('title' => 'UNIT', 'width' => 38, 'x' => 352),
    array('title' => 'BUY PRICE', 'width' => 75, 'x' => 390),
    array('title' => 'IMAGE', 'width' => 45, 'x' => 465),
    array('title' => 'DATE', 'width' => 82, 'x' => 510)
  );

  $table_x = 20;
  $table_width = 572;
  $row_height = 20; 
  $header_height = 22;

  $pages_contents = array();
  $current_page_content = '';

  // HEADER SECTION 
  $current_page_content .= "0.2 0.2 0.2 rg\n"; 
  $current_page_content .= "BT\n/F2 15 Tf\n20 755 Td\n(" . pdf_escape('Items List Report') . ") Tj\nET\n";
  $current_page_content .= "0.5 0.5 0.5 rg\n"; 
  $current_page_content .= "BT\n/F1 8.5 Tf\n20 740 Td\n(" . pdf_escape('A report of all items with quantities, units, categories, prices, and images.') . ") Tj\nET\n";

  // Badge ng Summary at Petsa sa kanang itaas (Pinalaki ang kahon pababa para magkasya ang 3 linya)
  $current_page_content .= "0.93 0.94 0.98 rg\n"; 
  $current_page_content .= "430 725 162 45 re f\n";
  $current_page_content .= "0.8 0.8 0.8 RG\n";
  $current_page_content .= "430 725 162 45 re S\n";
  
  $current_page_content .= "0.1 0.2 0.6 rg\n"; 
  $current_page_content .= "BT\n/F2 7.5 Tf\n436 758 Td\n(" . pdf_escape('Total Products: ' . count($products) . ' | Avail: ' . $total_available) . ") Tj\nET\n";
  $current_page_content .= "0.6 0.1 0.1 rg\n"; 
  $current_page_content .= "BT\n/F2 7.5 Tf\n436 746 Td\n(" . pdf_escape('Total Used Items: ' . $total_used) . ") Tj\nET\n";
  $current_page_content .= "0.4 0.4 0.4 rg\n";
  $current_page_content .= "BT\n/F1 7 Tf\n436 734 Td\n(" . pdf_escape('Generated: ' . date('F d, Y, g:i a')) . ") Tj\nET\n";

  // Gumuhit ng Table Header
  $row_y = 695;
  foreach ($columns as $column) {
    pdf_draw_cell($current_page_content, $column['x'], $row_y, $column['width'], $header_height, $column['title'], 'F2', 7.0, 'center', 2, '0.96 0.97 0.98', true);
  }
  $row_y -= $row_height;

  // I-populate ang mga Product Rows
  $count = 1;
  foreach ($products as $product) {
    // Pag-handle ng bagong pahina kapag marami ang produkto
    if ($row_y < 40) {
      $pages_contents[] = $current_page_content;
      $current_page_content = '';
      $row_y = 750;
      
      // I-reprint ang table header sa bagong pahina
      foreach ($columns as $column) {
        pdf_draw_cell($current_page_content, $column['x'], $row_y, $column['width'], $header_height, $column['title'], 'F2', 7.0, 'center', 2, '0.96 0.97 0.98', true);
      }
      $row_y -= $row_height;
    }

    // Zebra striping (salit-salit na kulay para sa malinis na presentation)
    $row_fill = ($count % 2 === 0) ? '0.98 0.98 0.99' : '1.0 1.0 1.0';
    foreach ($columns as $column) {
      pdf_draw_cell($current_page_content, $column['x'], $row_y, $column['width'], $row_height, '', 'F1', 7.0, 'left', 4, $row_fill, true);
    }

    // Pagkuha ng variables mula sa database row
    $p_id      = $count++;
    $p_name    = pdf_text_limit(remove_junk(first_character($product['name'])), 24);
    $p_cat     = pdf_text_limit(remove_junk(first_character($product['categorie'])), 18);
    $p_qty     = (int)$product['quantity'];
    $p_used    = isset($product['used']) ? (int)$product['used'] : 0; // Bagong variable para sa ginamit na items
    $p_unit    = !empty($product['unit']) ? remove_junk($product['unit']) : ''; 
    $p_price   = 'PHP ' . number_format((float) $product['buy_price'], 2);
    $p_img     = ($product['media_id'] === '0' || empty($product['image'])) ? '-' : 'Has Image';
    $p_date    = read_date($product['date']);

    // Isulat ang mga teksto sa bawat cell ng table row
    pdf_draw_cell($current_page_content, $columns[0]['x'], $row_y, $columns[0]['width'], $row_height, $p_id, 'F1', 7.0, 'center', 0, null, false);
    pdf_draw_cell($current_page_content, $columns[1]['x'], $row_y, $columns[1]['width'], $row_height, $p_name, 'F1', 7.0, 'left', 4, null, false);
    pdf_draw_cell($current_page_content, $columns[2]['x'], $row_y, $columns[2]['width'], $row_height, $p_cat, 'F1', 7.0, 'left', 4, null, false);
    
    // Available Qty Cell
    pdf_draw_cell($current_page_content, $columns[3]['x'], $row_y, $columns[3]['width'], $row_height, $p_qty, 'F1', 7.0, 'center', 0, null, false);
    // Used Qty Cell (Bagong salpak)
    pdf_draw_cell($current_page_content, $columns[4]['x'], $row_y, $columns[4]['width'], $row_height, $p_used, 'F1', 7.0, 'center', 0, null, false);
    
    pdf_draw_cell($current_page_content, $columns[5]['x'], $row_y, $columns[5]['width'], $row_height, $p_unit, 'F1', 7.0, 'center', 0, null, false);
    pdf_draw_cell($current_page_content, $columns[6]['x'], $row_y, $columns[6]['width'], $row_height, $p_price, 'F1', 7.0, 'left', 4, null, false);
    pdf_draw_cell($current_page_content, $columns[7]['x'], $row_y, $columns[7]['width'], $row_height, $p_img, 'F1', 7.0, 'center', 0, null, false);
    pdf_draw_cell($current_page_content, $columns[8]['x'], $row_y, $columns[8]['width'], $row_height, $p_date, 'F1', 6.0, 'left', 4, null, false);

    $row_y -= $row_height;
  }

  if (!empty($current_page_content)) {
    $pages_contents[] = $current_page_content;
  }

  // CORE PDF ENGINE ASSEMBLY
  $total_pages = count($pages_contents);
  $objects = array();
  
  $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
  
  $kids_str = '';
  $page_start_id = 7; 
  for ($p = 0; $p < $total_pages; $p++) {
    $obj_id = $page_start_id + ($p * 2);
    $kids_str .= "{$obj_id} 0 R ";
  }
  $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [" . rtrim($kids_str) . "] /Count {$total_pages} >>\nendobj\n";
  
  $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
  $objects[6] = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

  for ($p = 0; $p < $total_pages; $p++) {
    $page_obj_id = $page_start_id + ($p * 2);
    $stream_obj_id = $page_obj_id + 1;
    $p_content = $pages_contents[$p];
    
    $objects[$page_obj_id] = "{$page_obj_id} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents {$stream_obj_id} 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj\n";
    $objects[$stream_obj_id] = "{$stream_obj_id} 0 obj\n<< /Length " . strlen($p_content) . " >>\nstream\n" . $p_content . "\nendstream\nendobj\n";
  }

  ksort($objects);

  $pdf = "%PDF-1.4\n";
  $offsets = array();
  foreach ($objects as $id => $obj) {
    $offsets[$id] = strlen($pdf);
    $pdf .= $obj;
  }

  $startxref = strlen($pdf);
  $max_id = max(array_keys($objects));
  $pdf .= "xref\n0 " . ($max_id + 1) . "\n";
  $pdf .= "0000000000 65535 f \n";
  
  for ($i = 1; $i <= $max_id; $i++) {
    if (isset($offsets[$i])) {
      $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    } else {
      $pdf .= "0000000000 00000 f \n";
    }
  }
  
  $pdf .= "trailer\n<< /Size " . ($max_id + 1) . " /Root 1 0 R >>\n";
  $pdf .= "startxref\n" . $startxref . "\n%%EOF\n";

  // I-download o I-open ang maayos na PDF file
  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename="' . $filename . '"');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  header('Content-Length: ' . strlen($pdf));
  echo $pdf;
  exit;
?>