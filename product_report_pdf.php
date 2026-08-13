<?php
  $page_title = 'Facilities Inventory Report';
  require_once('includes/load.php');
  page_require_level(2);
  
  global $db;
  $products = array();
  
  $sql = "SELECT p.id, p.name, p.quantity, p.used, p.unit, p.buy_price, p.media_id, p.date, c.name AS categorie_name, m.file_name AS image, p.remarks ";
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
  
  if (empty($products)) $products = [];

  $filename = 'Facilities_Inventory_' . date('Ymd_His') . '.pdf';

  function pdf_escape($text) {
      return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string)$text);
  }

  function pdf_fit_text($text, $width, $font_size = 7) {
      // 1. Gawin ang padding buffer na mas malaki (10 units)
      // 2. Gamitin ang 0.5 multiplier para mas mabilis putulin ang text (mas conservative)
      $available_width = $width - 10;
      $avg_char_width = ($font_size * 0.5); 
      $max_chars = max(1, floor($available_width / $avg_char_width));
      
      if (strlen($text) > $max_chars) {
          return substr($text, 0, max(1, $max_chars - 3)) . '...';
      }
      return $text;
  }

  function pdf_draw_cell(&$content, $x, $y, $width, $height, $text, $font = 'F1', $size = 7, $align = 'center', $bg = null) {
      if ($bg) {
          $content .= "{$bg} rg\n{$x} {$y} {$width} {$height} re f\n";
      }
      $content .= "0.5 0.5 0.5 RG\n{$x} {$y} {$width} {$height} re S\n0 0 0 rg\n";
      
      // I-calculate ang text position base sa 0.5 multiplier para consistent
      $text_width = strlen($text) * ($size * 0.5);
      $text_x = $x + 4; // Dagdagan ang start padding
      if ($align === 'center') {
          $text_x = $x + (($width - $text_width) / 2);
      }

      $content .= "BT\n/{$font} {$size} Tf\n{$text_x} " . ($y + ($height / 3)) . " Td\n(" . pdf_escape($text) . ") Tj\nET\n";
  }

  $logo_path = __DIR__ . '/uploads/images/logo.jpg';
  $logo_data = '';
  $logo_width = 0;
  $logo_height = 0;

  if (file_exists($logo_path)) {
      $image_info = getimagesize($logo_path);
      if ($image_info && $image_info[2] === IMAGETYPE_JPEG) {
          $logo_data = file_get_contents($logo_path);
          $logo_width = (int)$image_info[0];
          $logo_height = (int)$image_info[1];
      }
  }

  function pdf_draw_text(&$content, $x, $y, $text, $font = 'F1', $size = 9, $align = 'left') {
      $text_width = strlen($text) * ($size * 0.5);
      if ($align === 'center') {
          $x = $x - ($text_width / 2);
      } elseif ($align === 'right') {
          $x = $x - $text_width;
      }
      $content .= "BT\n/{$font} {$size} Tf\n{$x} {$y} Td\n(" . pdf_escape($text) . ") Tj\nET\n";
  }

  $header_content = "";
  if (!empty($logo_data)) {
      $logo_display_width = 70;
      $logo_display_height = 70;
      $header_content .= "q\n";
      $header_content .= "{$logo_display_width} 0 0 {$logo_display_height} 40 700 cm\n";
      $header_content .= "/Logo Do\nQ\n";
  }

  $header_content .= "0 0 0 RG\n1 w\n40 690 m 572 690 l S\n";
  $header_content .= "BT\n/F2 14 Tf\n120 745 Td\n(" . pdf_escape('The Ripple of Hope Skills and Technology Institute Inc.') . ") Tj\nET\n";
  $header_content .= "BT\n/F1 9 Tf\n120 728 Td\n(" . pdf_escape('SYMAR BLDG., VINZONS AVE, BRGY VII, DAET, CAMARINES NORTE') . ") Tj\nET\n";
  $header_content .= "BT\n/F1 9 Tf\n120 716 Td\n(" . pdf_escape('School I.D. No. 408869') . ") Tj\nET\n";
  $header_content .= "BT\n/F2 11 Tf\n120 698 Td\n(" . pdf_escape('Facilities Inventory Record') . ") Tj\nET\n";

  $row_y = 660;
  $row_h = 20;
  $cols = [
      ['t' => 'DATE', 'w' => 50],
      ['t' => 'DESCRIPTION', 'w' => 140],
      ['t' => 'QTY', 'w' => 30],
      ['t' => 'UNIT', 'w' => 30],
      ['t' => 'UNIT COST', 'w' => 60],
      ['t' => 'TOTAL COST', 'w' => 60],
      ['t' => 'CATEGORY', 'w' => 80],
      ['t' => 'REMARKS', 'w' => 80]
  ];

  $content = $header_content;
  $cur_x = 20;
  foreach ($cols as $c) {
      pdf_draw_cell($content, $cur_x, $row_y, $c['w'], $row_h, $c['t'], 'F2', 7, 'center', '0.85 0.85 0.85');
      $cur_x += $c['w'];
  }
  $row_y -= $row_h;

  foreach ($products as $p) {
      if ($row_y < 50) {
          $pages_contents[] = $content;
          $content = $header_content;
          $row_y = 660;
          $cur_x = 20;
          foreach ($cols as $c) {
              pdf_draw_cell($content, $cur_x, $row_y, $c['w'], $row_h, $c['t'], 'F2', 7, 'center', '0.85 0.85 0.85');
              $cur_x += $c['w'];
          }
          $row_y -= $row_h;
      }

      $total = (float)$p['quantity'] * (float)$p['buy_price'];
      $short_date = date('m/d/Y', strtotime($p['date']));
      $cat_name = !empty($p['categorie_name']) ? $p['categorie_name'] : 'N/A';

      $row_data = [
          $short_date,
          pdf_fit_text($p['name'], 140),
          $p['quantity'],
          $p['unit'] ?? '-',
          number_format($p['buy_price'], 2),
          number_format($total, 2),
          pdf_fit_text($cat_name, 80),
          pdf_fit_text(!empty($p['remarks']) ? $p['remarks'] : '', 80)
      ];

      $cur_x = 20;
      foreach ($row_data as $i => $val) {
          pdf_draw_cell($content, $cur_x, $row_y, $cols[$i]['w'], $row_h, (string)$val, 'F1', 7, 'center');
          $cur_x += $cols[$i]['w'];
      }
      $row_y -= $row_h;
  }

  $pages_contents[] = $content;

  $objects = [];
  $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
  $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [5 0 R] /Count 1 >>\nendobj\n";
  $objects[3] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
  $objects[4] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

  $resource_string = "<< /Font << /F1 3 0 R /F2 4 0 R >>";
  if (!empty($logo_data)) {
      $objects[7] = "7 0 obj\n<< /Type /XObject /Subtype /Image /Width {$logo_width} /Height {$logo_height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($logo_data) . " >>\nstream\n" . $logo_data . "\nendstream\nendobj\n";
      $resource_string .= " /XObject << /Logo 7 0 R >>";
  }
  $resource_string .= " >> >>";

  $objects[5] = "5 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 6 0 R /Resources {$resource_string} >>\nendobj\n";
  $objects[6] = "6 0 obj\n<< /Length " . strlen($pages_contents[0]) . " >>\nstream\n" . $pages_contents[0] . "\nendstream\nendobj\n";

  $pdf = "%PDF-1.4\n";
  $offsets = [0 => strlen($pdf)];
  foreach ($objects as $obj_id => $obj_text) {
      $offsets[$obj_id] = strlen($pdf);
      $pdf .= $obj_text;
  }

  $xref = "xref\n0 " . (count($objects) + 1) . "\n";
  $xref .= sprintf("%010d 65535 f \n", 0);
  for ($i = 1; $i <= count($objects); $i++) {
      $xref .= sprintf("%010d 00000 n \n", $offsets[$i]);
  }

  $pdf .= $xref;
  $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
  $pdf .= "startxref\n" . strlen($pdf) . "\n%%EOF";

  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename="' . $filename . '"');
  echo $pdf;
  exit;
?>