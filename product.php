<?php
  $page_title = 'Add Product';
  require_once('includes/load.php');
  // Check kung anong level ng user ang may permission na makita ito
  page_require_level(2);
  $all_categories = find_all('categories');
  $products = join_product_table();
  
  // Detect if DB has optional columns
  $has_unit = function_exists('columnExists') && columnExists('products', 'unit');
  $has_serial = function_exists('columnExists') && columnExists('products', 'serial_number');
  $has_receipt = function_exists('columnExists') && columnExists('products', 'receipt_number');

  // Prefill support: if ?prefill=ID is present, load that product to auto-fill the form
  $prefill = null;
  if (isset($_GET['prefill']) && is_numeric($_GET['prefill'])) {
    $pid = (int) $_GET['prefill'];
    $prefill = find_by_id('products', $pid);
  }
  // Also accept prefill via POST (when form submits back for update)
  if (empty($prefill) && isset($_POST['prefill_id']) && is_numeric($_POST['prefill_id'])) {
    $pid = (int) $_POST['prefill_id'];
    $prefill = find_by_id('products', $pid);
  }
?>
<?php
 if(isset($_POST['add_product'])){
   // Required fields adapt to available columns
   $req_fields = array('product-title','product-categorie','product-quantity','buying-price','product-date');
   if ($has_unit) {
     $req_fields[] = 'product-unit';
   }
   validate_fields($req_fields);
   
   if(empty($errors)){
     $p_name  = remove_junk($db->escape($_POST['product-title']));
     $p_cat   = remove_junk($db->escape($_POST['product-categorie']));
     $p_qty   = (int)remove_junk($db->escape($_POST['product-quantity']));
     $p_buy   = remove_junk($db->escape($_POST['buying-price']));
     $p_unit  = $has_unit ? remove_junk($db->escape($_POST['product-unit'])) : '';
     
     if(!is_numeric($p_qty) || $p_qty < 1){
       $errors[] = 'Quantity must be a positive number.';
     }
    if(!is_numeric($p_buy) || (float)$p_buy < 0){
      $errors[] = 'Price must be a non-negative number.';
    }
     if($has_unit && $p_unit === ''){
       $errors[] = 'Please select a unit.';
     }
     
     if(empty($errors)){
       if (isset($_POST['product-date']) && $_POST['product-date'] !== '') {
         $p_date = date('Y-m-d H:i:s', strtotime(remove_junk($db->escape($_POST['product-date']))));
       } else {
         $p_date = make_date();
       }
       
      if(isset($_FILES['product-photo']) && isset($_FILES['product-photo']['error']) && $_FILES['product-photo']['error'] != 4){
         $photo = new Media();
         if($photo->upload($_FILES['product-photo'])){
           if($photo->process_media()){
             $media_id = $db->insert_id();
           } else {
             $session->msg('d', join(' ', $photo->errors));
             redirect('add_product.php', false);
           }
         } else {
           $session->msg('d', join(' ', $photo->errors));
           redirect('add_product.php', false);
         }
       } else {
         $media_id = '0';
       }

       // If a prefill id exists, perform update instead of insert
       $editing_id = null;
       if (!empty($prefill) && isset($prefill['id'])) {
         $editing_id = (int)$prefill['id'];
       } elseif (isset($_POST['prefill_id']) && is_numeric($_POST['prefill_id'])) {
         $editing_id = (int)$_POST['prefill_id'];
       }

       if ($editing_id) {
         // Prepare update columns
         $columns = array(
           "name = '{$p_name}'",
           "quantity = '{$p_qty}'",
           "buy_price = '{$p_buy}'",
           "categorie_id = '{$p_cat}'",
           "media_id = '{$media_id}'",
           "date = '{$p_date}'"
         );
         if ($has_unit) {
           $columns[] = "unit = '{$p_unit}'";
         }
         if ($has_serial) {
           $p_serial = isset($_POST['product-serial']) ? remove_junk($db->escape($_POST['product-serial'])) : '';
           $columns[] = "serial_number = '{$p_serial}'";
         }
         if ($has_receipt) {
           $p_receipt = isset($_POST['product-receipt']) ? remove_junk($db->escape($_POST['product-receipt'])) : '';
           $columns[] = "receipt_number = '{$p_receipt}'";
         }

         $query = "UPDATE products SET " . implode(', ', $columns) . " WHERE id = '{$editing_id}'";
         if($db->query($query)){
           $session->msg('s',"Product updated successfully.");
           redirect('product_list.php', false);
         } else {
           $session->msg('d',' Sorry failed to update!');
           redirect('add_product.php?prefill=' . $editing_id, false);
         }
       }

       // Build insert columns dynamically based on available schema
       if ($has_unit) {
         $columns = array('name','quantity','unit','buy_price','categorie_id','media_id','date');
         $values  = array("'{$p_name}'","'{$p_qty}'","'{$p_unit}'","'{$p_buy}'","'{$p_cat}'","'{$media_id}'","'{$p_date}'");
       } else {
         $columns = array('name','quantity','buy_price','categorie_id','media_id','date');
         $values  = array("'{$p_name}'","'{$p_qty}'","'{$p_buy}'","'{$p_cat}'","'{$media_id}'","'{$p_date}'");
       }
       if ($has_serial) {
         $p_serial = isset($_POST['product-serial']) ? remove_junk($db->escape($_POST['product-serial'])) : '';
         $columns[] = 'serial_number';
         $values[]  = "'{$p_serial}'";
       }
       if ($has_receipt) {
         $p_receipt = isset($_POST['product-receipt']) ? remove_junk($db->escape($_POST['product-receipt'])) : '';
         $columns[] = 'receipt_number';
         $values[]  = "'{$p_receipt}'";
       }
       
       $query = "INSERT INTO products (".implode(', ', $columns).") VALUES (".implode(', ', $values).")";

       if($db->query($query)){
         $session->msg('s',"Product added successfully. ");
         redirect('add_product.php', false);
       } else {
         $session->msg('d',' Sorry failed to added!');
         redirect('add_product.php', false);
       }
     } else {
       $session->msg("d", $errors);
       redirect('add_product.php', false);
     }

   } else{
     $session->msg("d", $errors);
     redirect('add_product.php',false);
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
            <span class="glyphicon glyphicon-th"></span>
            <span>Add New Items</span>
          </strong>
        </div>
        <div class="panel-body">
         <div class="col-md-12">
          <form method="post" action="" class="clearfix" enctype="multipart/form-data">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-th-large"></i>
                  </span>
                  <input type="text" class="form-control" name="product-title" placeholder="Product Title" required value="<?php echo $prefill ? htmlspecialchars($prefill['name'], ENT_QUOTES, 'UTF-8') : '' ?>">
               </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-md-6">
                    <select class="form-control" name="product-categorie" required>
                      <option value="">Select Item Category</option>
                    <?php  foreach ($all_categories as $cat): ?>
                      <option value="<?php echo (int)$cat['id'] ?>" <?php echo $prefill && (int)$prefill['categorie_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?php echo $cat['name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="product-photo" style="display:block; color:#888; margin-bottom:5px;">Upload Product Image</label>
                      <div id="product-photo-preview" style="cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:120px; height:120px; border:2px dashed #ccc; background:#fafafa; color:#666; font-size:32px; overflow:hidden; position:relative; background-size:cover; background-position:center;">
                        <span id="product-photo-placeholder" style="display:flex; align-items:center; justify-content:center; width:100%; height:100%;">+</span>
                        <img id="product-photo-img" src="" alt="Product Preview" style="display:none; width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0;" />
                      </div>
                      <input type="file" id="product-photo" name="product-photo" accept="image/*" style="display:none;">
                      <p class="help-block" style="margin:5px 0 0;">Click the box to choose an image.</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group">
               <div class="row">
                 <div class="col-md-4">
                   <label for="product-quantity" style="display:block; color:#888; margin-bottom:5px;">Quantity</label>
                   <div class="input-group" style="display: flex; width: 100%;">
                     <span class="input-group-addon" style="width: auto;">
                       <i class="glyphicon glyphicon-shopping-cart"></i>
                     </span>
                     <input type="number" id="product-quantity" class="form-control" name="product-quantity" placeholder="Product Quantity" min="1" step="1" required style="flex: 1; border-top-right-radius: 0; border-bottom-right-radius: 0;" value="<?php echo $prefill ? (int)$prefill['quantity'] : '' ?>">
                   </div>
                 </div>
                 
                 <div class="col-md-4">
                   <?php if ($has_unit): ?>
                   <label for="product-unit" style="display:block; color:#888; margin-bottom:5px;">Unit</label>
                   <select id="product-unit" class="form-control" name="product-unit" required>
                     <option value="">Select Unit</option>
                     <option value="piece" <?php echo $prefill && $prefill['unit'] === 'piece' ? 'selected' : '' ?>>Piece</option>
                     <option value="pack" <?php echo $prefill && $prefill['unit'] === 'pack' ? 'selected' : '' ?>>Pack</option>
                     <option value="box" <?php echo $prefill && $prefill['unit'] === 'box' ? 'selected' : '' ?>>Box</option>
                   </select>
                   <?php endif; ?>
                 </div>
                 
                 <div class="col-md-4">
                   <label for="buying-price" style="display:block; color:#888; margin-bottom:5px;">Buying Price</label>
                   <div class="input-group">
                     <span class="input-group-addon">PHP</span>
                     <input type="number" id="buying-price" class="form-control" name="buying-price" placeholder="0.00" min="0" step="0.01" required value="<?php echo $prefill ? htmlspecialchars($prefill['buy_price'], ENT_QUOTES, 'UTF-8') : '' ?>">
                  </div>
                 </div>
               </div>
              </div>
              
             <?php if ($has_serial || $has_receipt): ?>
              <div class="form-group">
               <div class="row">
                <?php if ($has_serial): ?>
                 <div class="<?php echo $has_receipt ? 'col-md-6' : 'col-md-12'; ?>">
                   <label for="product-serial" style="display:block; color:#888; margin-bottom:5px;">Serial Number</label>
                   <input type="text" id="product-serial" class="form-control" name="product-serial" placeholder="Serial Number" value="<?php echo $prefill ? htmlspecialchars($prefill['serial_number'], ENT_QUOTES, 'UTF-8') : '' ?>">
                 </div>
                <?php endif; ?>
                <?php if ($has_receipt): ?>
                 <div class="<?php echo $has_serial ? 'col-md-6' : 'col-md-12'; ?>">
                   <label for="product-receipt" style="display:block; color:#888; margin-bottom:5px;">Receipt Number</label>
                   <input type="text" id="product-receipt" class="form-control" name="product-receipt" placeholder="Receipt Number" value="<?php echo $prefill ? htmlspecialchars($prefill['receipt_number'], ENT_QUOTES, 'UTF-8') : '' ?>">
                 </div>
                <?php endif; ?>
               </div>
              </div>
             <?php endif; ?>
              <div class="form-group">
               <div class="row">
                 <div class="col-md-6">
                   <div class="input-group">
                     <span class="input-group-addon">
                       <i class="glyphicon glyphicon-calendar"></i>
                     </span>
                     <input type="datetime-local" class="form-control" name="product-date" value="<?php echo $prefill ? date('Y-m-d\\TH:i', strtotime($prefill['date'])) : date('Y-m-d\\TH:i'); ?>">
                  </div>
                 </div>
               </div>
              </div>
              
              <div style="display: inline-flex; gap: 5px; flex-wrap: wrap;">
                    <?php if(!empty($prefill)): ?>
                      <input type="hidden" name="prefill_id" value="<?php echo (int)$prefill['id']; ?>">
                    <?php endif; ?>
                    <button type="submit" name="add_product" class="btn btn-danger"><?php echo !empty($prefill) ? 'Update Item' : 'Add Item'; ?></button>
                <a href="add_sale.php" class="btn btn-warning">Get Items</a>
                <a href="product_list.php" class="btn btn-info">View Item List</a>
                <a href="product_report.php" class="btn btn-success">Item List Report</a>
              </div>
              
          </form>
         </div>
        </div>
      </div>
    </div>
  </div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var photoInput = document.getElementById('product-photo');
    var previewBox = document.getElementById('product-photo-preview');
    var photoImg = document.getElementById('product-photo-img');
    var placeholder = document.getElementById('product-photo-placeholder');

    if (!photoInput || !previewBox || !photoImg || !placeholder) return;

    previewBox.addEventListener('click', function() {
      photoInput.click();
    });

    photoInput.addEventListener('change', function(event) {
      var file = event.target.files[0];
      if (!file) {
        photoImg.style.display = 'none';
        placeholder.style.display = 'flex';
        photoImg.src = '';
        return;
      }

      var reader = new FileReader();
      reader.onload = function(e) {
        photoImg.src = e.target.result;
        previewBox.style.backgroundImage = 'url(' + e.target.result + ')';
        placeholder.style.display = 'none';
      };
      reader.readAsDataURL(file);
    });
  });
</script>

<?php include_once('layouts/footer.php'); ?>