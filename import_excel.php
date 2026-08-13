<?php
  use PhpOffice\PhpSpreadsheet\IOFactory;

  $page_title = 'Import Products';
  require_once('includes/load.php');
  page_require_level(2);

  $excel_library_missing = !file_exists(__DIR__ . '/vendor/autoload.php');
  if ($excel_library_missing) {
    $_SESSION['msg'] = 'Excel import support is not installed in this project. Please run Composer install in the project root before uploading sheets.';
  }

  $all_categories = find_all('categories');
  $products = join_product_table();

  $has_unit = function_exists('columnExists') && columnExists('products', 'unit');
  $has_serial = function_exists('columnExists') && columnExists('products', 'serial_number');
  $has_receipt = function_exists('columnExists') && columnExists('products', 'receipt_number');
?>
<?php
 if ($excel_library_missing) {
   $session->msg('d', 'Excel import support is not installed. Please run Composer install in the project root and refresh this page.');
   redirect('product_list.php', false);
 }

 if (isset($_POST['import_excel'])) {

   $import_cat_id = isset($_POST['import-categorie']) ? (int)$_POST['import-categorie'] : 0;

   if ($import_cat_id <= 0) {
     $session->msg('d', 'Please select a category for the imported items.');
     redirect('import_excel.php', false);
   } elseif (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
     $session->msg('d', 'Please choose a valid Excel file to upload.');
     redirect('import_excel.php', false);
   } else {

     $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
     if (!in_array($ext, ['xlsx', 'xls'])) {
       $session->msg('d', 'Please upload a .xlsx or .xls file.');
       redirect('import_excel.php', false);
     }

     require_once __DIR__ . '/vendor/autoload.php';

     try {
       $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
       $inserted = 0;
       $updated  = 0;
       $skipped  = 0;

       $existing_by_serial = array();
       $existing_by_name    = array();
       foreach ($products as $p) {
         if (!empty($p['serial_number'])) {
           $existing_by_serial[strtolower(trim($p['serial_number']))] = $p['id'];
         }
         $existing_by_name[strtolower(trim($p['name']))] = $p['id'];
       }

       foreach ($spreadsheet->getAllSheets() as $sheet) {
         $rows = $sheet->toArray(null, true, true, true);
         $colMap = null;

         foreach ($rows as $row) {

           $foundHeader = false;
           foreach ($row as $val) {
             if (strpos(strtoupper(trim((string)$val)), 'DESCRIPTION') !== false) {
               $foundHeader = true;
               break;
             }
           }

           if ($foundHeader) {
             $colMap = array();
             foreach ($row as $col => $val) {
               $v = strtoupper(trim((string)$val));
               if ($v === '') continue;
               if (strpos($v, 'DESCRIPTION') !== false) {
                 $colMap['description'] = $col;
               } elseif ($v === 'QTY' || (strpos($v, 'QUANTITY') !== false && strpos($v, 'HAND') === false)) {
                 $colMap['qty'] = $col;
               } elseif ($v === 'UNIT') {
                 $colMap['unit'] = $col;
               } elseif (strpos($v, 'UNIT COST') !== false || strpos($v, 'UNIT PRICE') !== false) {
                 $colMap['unit_cost'] = $col;
               } elseif (strpos($v, 'SERIAL') !== false) {
                 $colMap['serial'] = $col;
               } elseif (strpos($v, 'RECEI') !== false || strpos($v, 'RECIE') !== false) {
                 $colMap['receipt'] = $col;
               }
             }
             continue;
           }

           if ($colMap === null || !isset($colMap['description'])) continue;

           $desc = trim((string)($row[$colMap['description']] ?? ''));

           if ($desc === '' || stripos($desc, 'INSTITUTE') !== false
               || stripos($desc, 'FACILITIES INVENTORY') !== false
               || stripos($desc, 'SEC. REG') !== false
               || stripos(trim($desc), 'SY ') === 0) {
             $colMap = null;
             continue;
           }

           $qty       = isset($colMap['qty']) ? (float)($row[$colMap['qty']] ?? 0) : 0;
           $unit      = isset($colMap['unit']) ? trim((string)($row[$colMap['unit']] ?? '')) : 'piece';
           $unit_cost = isset($colMap['unit_cost'])
               ? (float)preg_replace('/[^0-9.]/', '', (string)($row[$colMap['unit_cost']] ?? 0))
               : 0;
           $serial  = isset($colMap['serial'])  ? trim((string)($row[$colMap['serial']] ?? ''))  : '';
           $receipt = isset($colMap['receipt']) ? trim((string)($row[$colMap['receipt']] ?? '')) : '';

           if ($unit === '') $unit = 'piece';
           if ($qty <= 0 && $unit_cost <= 0) { $skipped++; continue; }

           $p_name = remove_junk($db->escape($desc));
           $p_unit = remove_junk($db->escape($unit));
           $p_serial  = remove_junk($db->escape($serial));
           $p_receipt = remove_junk($db->escape($receipt));

           $match_id = null;
           if ($serial !== '' && isset($existing_by_serial[strtolower($serial)])) {
             $match_id = $existing_by_serial[strtolower($serial)];
           } elseif (isset($existing_by_name[strtolower($desc)])) {
             $match_id = $existing_by_name[strtolower($desc)];
           }

           if ($match_id) {
             $columns = array(
               "quantity = '{$qty}'",
               "buy_price = '{$unit_cost}'",
             );
             if ($has_unit) $columns[] = "unit = '{$p_unit}'";
             if ($has_receipt && $receipt !== '') $columns[] = "receipt_number = '{$p_receipt}'";

             $query = "UPDATE products SET " . implode(', ', $columns) . " WHERE id = '{$match_id}'";
             if ($db->query($query)) { $updated++; } else { $skipped++; }
           } else {
             if ($has_unit) {
               $columns = array('name','quantity','unit','buy_price','categorie_id','media_id','date');
               $values  = array("'{$p_name}'","'{$qty}'","'{$p_unit}'","'{$unit_cost}'","'{$import_cat_id}'","'0'","'" . make_date() . "'");
             } else {
               $columns = array('name','quantity','buy_price','categorie_id','media_id','date');
               $values  = array("'{$p_name}'","'{$qty}'","'{$unit_cost}'","'{$import_cat_id}'","'0'","'" . make_date() . "'");
             }
             if ($has_serial) { $columns[] = 'serial_number'; $values[] = "'{$p_serial}'"; }
             if ($has_receipt) { $columns[] = 'receipt_number'; $values[] = "'{$p_receipt}'"; }

             $query = "INSERT INTO products (".implode(', ', $columns).") VALUES (".implode(', ', $values).")";
             if ($db->query($query)) {
               $inserted++;
               $existing_by_name[strtolower($desc)] = $db->insert_id();
             } else {
               $skipped++;
             }
           }
         }
       }

       $session->msg('s', "Import done: {$inserted} added, {$updated} updated, {$skipped} skipped.");
       redirect('product_list.php', false);

     } catch (Exception $e) {
       $session->msg('d', 'Failed to read the file: ' . $e->getMessage());
       redirect('import_excel.php', false);
     }
   }
 }
?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>
<div class="row">
  <div class="col-md-8">
    <div class="panel panel-default">
      <div class="panel-heading">
        <strong>
          <span class="glyphicon glyphicon-import"></span>
          <span>Import Items from Excel</span>
        </strong>
      </div>
      <div class="panel-body">
        <form method="post" action="" enctype="multipart/form-data">
          <div class="form-group">
            <label for="excel_file" style="display:block; color:#888; margin-bottom:5px;">Excel File (.xlsx)</label>
            <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" class="form-control" required>
            <p class="help-block">Gamitin ang parehong layout ng "Facilities Inventory Record" sheet (Description, Qty, Unit, Unit Cost, Serial No., Receipt No.).</p>
          </div>
          <div class="form-group">
            <label for="import-categorie" style="display:block; color:#888; margin-bottom:5px;">Category (ilalapat sa lahat ng bagong item)</label>
            <select id="import-categorie" class="form-control" name="import-categorie" required>
              <option value="">Select Item Category</option>
              <?php foreach ($all_categories as $cat): ?>
                <option value="<?php echo (int)$cat['id'] ?>"><?php echo $cat['name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" name="import_excel" class="btn btn-info">
            <span class="glyphicon glyphicon-upload"></span> Import
          </button>
          <a href="product.php" class="btn btn-default">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>