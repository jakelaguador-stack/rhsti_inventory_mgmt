<?php
  $page_title = 'Edit product';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(2);
?>
<?php
$product = find_by_id('products',(int)$_GET['id']);
$all_categories = find_all('categories');
$all_photo = find_all('media');
$has_unit = function_exists('columnExists') && columnExists('products', 'unit');
$has_serial = function_exists('columnExists') && columnExists('products', 'serial_number');
$has_receipt = function_exists('columnExists') && columnExists('products', 'receipt_number');
// current media for preview
$current_photo = null;
if(isset($product['media_id']) && (int)$product['media_id'] > 0){
  $current_photo = find_by_id('media',(int)$product['media_id']);
}
if(!$product){
  $session->msg("d","Missing product id.");
  redirect('product.php');
}
?>
<?php
 if(isset($_POST['product'])){
   $req_fields = array('product-title','product-categorie','product-available','product-used','buying-price','product-date');
   if($has_unit){
     $req_fields[] = 'product-unit';
   }
    validate_fields($req_fields);

   if(empty($errors)){
       $p_name  = remove_junk($db->escape($_POST['product-title']));
       $p_cat   = (int)$_POST['product-categorie'];
       $p_available = (int)remove_junk($db->escape($_POST['product-available']));
       $p_used  = (int)remove_junk($db->escape($_POST['product-used']));
       $p_buy   = remove_junk($db->escape($_POST['buying-price']));
       $p_unit  = $has_unit ? remove_junk($db->escape($_POST['product-unit'])) : '';
       $p_serial = $has_serial ? remove_junk($db->escape($_POST['product-serial'] ?? '')) : '';
       $p_receipt = $has_receipt ? remove_junk($db->escape($_POST['product-receipt'] ?? '')) : '';
       if(!is_numeric($p_available) || (int)$p_available < 0){
         $errors[] = 'Available items must be zero or more.';
       }
       if(!is_numeric($p_used) || (int)$p_used < 0){
         $errors[] = 'Used items must be zero or more.';
       }
       if(!is_numeric($p_buy) || (float)$p_buy <= 0){
         $errors[] = 'Price must be a positive number.';
       }
       if($has_unit && $p_unit === ''){
         $errors[] = 'Please select a unit.';
       }
       if(empty($errors)){
         $p_qty = $p_available + $p_used;
         $p_date  = date('Y-m-d H:i:s', strtotime(remove_junk($db->escape($_POST['product-date']))));
         // Handle uploaded image (if any)
         if(isset($_FILES['product-photo']) && isset($_FILES['product-photo']['error']) && $_FILES['product-photo']['error'] != 4){
           $photo = new Media();
           if($photo->upload($_FILES['product-photo'])){
             if($photo->process_media()){
               // media record inserted, get its id
               $media_id = $db->insert_id();
             } else {
               $session->msg('d', join(' ', $photo->errors));
               redirect('edit_product.php?id=' . $product['id'], false);
             }
           } else {
             $session->msg('d', join(' ', $photo->errors));
             redirect('edit_product.php?id=' . $product['id'], false);
           }
         } else {
           // No new upload; keep existing media id
           $media_id = (int)$product['media_id'];
         }
         $columns = array(
           "name = '{$p_name}'",
           "quantity = '{$p_qty}'",
           "used = '{$p_used}'",
           "buy_price = '{$p_buy}'",
           "categorie_id = '{$p_cat}'",
           "media_id = '{$media_id}'",
           "date = '{$p_date}'"
         );
         if($has_unit){
           $columns[] = "unit = '{$p_unit}'";
         }
         if($has_serial){
           $columns[] = "serial_number = '{$p_serial}'";
         }
         if($has_receipt){
           $columns[] = "receipt_number = '{$p_receipt}'";
         }
         $query   = "UPDATE products SET ".implode(', ', $columns);
         $query  .=" WHERE id ='{$product['id']}'";
         $result = $db->query($query);
         if($result && $db->affected_rows() === 1){
           $session->msg('s',"Product updated ");
           redirect('product.php', false);
         } else {
           $session->msg('d',' Sorry failed to updated!');
           redirect('edit_product.php?id='.$product['id'], false);
         }
       } else {
         $session->msg("d", $errors);
         redirect('edit_product.php?id='.$product['id'], false);
       }

   } else{
       $session->msg("d", $errors);
       redirect('edit_product.php?id='.$product['id'], false);
   }

 }

?>
<?php include_once('layouts/header.php'); ?>
<script>
function syncStockTotals() {
  var available = parseInt(document.getElementById('product-available').value, 10) || 0;
  var used = parseInt(document.getElementById('product-used').value, 10) || 0;
  document.querySelector('input[name="product-quantity"]').value = available + used;
}

document.addEventListener('DOMContentLoaded', function () {
  var availableInput = document.getElementById('product-available');
  var usedInput = document.getElementById('product-used');
  if (availableInput && usedInput) {
    availableInput.addEventListener('input', syncStockTotals);
    usedInput.addEventListener('input', syncStockTotals);
  }
});
</script>
<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>
  <div class="row">
      <div class="panel panel-default">
        <div class="panel-heading">
          <strong>
            <span class="glyphicon glyphicon-th"></span>
            <span>Edit Existing Items</span>
          </strong>
        </div>
        <div class="panel-body">
         <div class="col-md-7">
           <form method="post" action="edit_product.php?id=<?php echo (int)$product['id'] ?>" enctype="multipart/form-data">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-th-large"></i>
                  </span>
                  <input type="text" class="form-control" name="product-title" value="<?php echo remove_junk($product['name']);?>">
               </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-md-4">
                    <select class="form-control" name="product-categorie">
                    <option value=""> Select a categorie</option>
                   <?php  foreach ($all_categories as $cat): ?>
                     <option value="<?php echo (int)$cat['id']; ?>" <?php if($product['categorie_id'] === $cat['id']): echo "selected"; endif; ?> >
                       <?php echo remove_junk($cat['name']); ?></option>
                   <?php endforeach; ?>
                 </select>
                  </div>
                  <div class="col-md-6">
                    <?php if($current_photo): ?>
                      <div class="form-group">
                        <label>Current Image</label>
                        <div>
                          <img src="uploads/products/<?php echo $current_photo['file_name']; ?>" alt="Current Image" style="max-width:120px;max-height:120px;" />
                        </div>
                      </div>
                    <?php endif; ?>
                    <div class="form-group">
                      <label for="product-photo">Upload Image</label>
                      <div style="display:flex; align-items:center; gap:10px;">
                        <label for="product-photo" style="cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:120px; height:120px; border:2px dashed #ccc; background:#fafafa; color:#666; font-size:36px;">
                          <span class="glyphicon glyphicon-plus"></span>
                        </label>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                          <input type="file" id="product-photo" name="product-photo" accept="image/*" style="display:none;">
                          <span class="help-block" style="margin:0; padding:0;">Click the plus sign to upload a new image.</span>
                          <span class="help-block" style="margin:0; padding:0;">Leave empty to keep existing image.</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group">
               <div class="row">
                 <div class="col-md-8">
                  <div class="form-group">
                    <label for="qty">Stock Record</label>
                    <small class="text-muted" style="display:block; margin-bottom:8px;">Available + Used will be saved as the total quantity.</small>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="input-group">
                          <span class="input-group-addon">
                            <i class="glyphicon glyphicon-ok"></i>
                          </span>
                          <input type="number" class="form-control" name="product-available" id="product-available" value="<?php echo max(0, (int)$product['quantity'] - (int)($product['used'] ?? 0)); ?>" min="0" step="1" required>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="input-group">
                          <span class="input-group-addon">
                            <i class="glyphicon glyphicon-fire"></i>
                          </span>
                          <input type="number" class="form-control" name="product-used" id="product-used" value="<?php echo (int)($product['used'] ?? 0); ?>" min="0" step="1" required>
                        </div>
                      </div>
                    </div>
                  </div>
                 </div>
                 <div class="col-md-4">
                  <div class="form-group">
                    <label for="qty">Buying price</label>
                    <div class="input-group">
                      <span class="input-group-addon">
                        <i class="glyphicon glyphicon-usd"></i>
                      </span>
                      <input type="number" class="form-control" name="buying-price" value="<?php echo remove_junk($product['buy_price']);?>" min="0.01" step="0.01" required>
                      <span class="input-group-addon">.00</span>
                    </div>
                  </div>
                 </div>
               </div>
              </div>
              <input type="hidden" name="product-quantity" value="<?php echo (int)$product['quantity']; ?>">
              <div class="form-group">
                <div class="row">
                  <div class="col-md-6">
                    <label for="date">Date</label>
                    <div class="input-group">
                      <span class="input-group-addon">
                        <i class="glyphicon glyphicon-calendar"></i>
                      </span>
                      <input type="datetime-local" class="form-control" name="product-date" value="<?php echo date('Y-m-d\TH:i', strtotime($product['date'])); ?>">
                    </div>
                  </div>
                  <?php if($has_unit): ?>
                  <div class="col-md-6">
                    <label for="product-unit">Product Unit</label>
                    <div class="input-group">
                      <span class="input-group-addon">
                        <i class="glyphicon glyphicon-th-list"></i>
                      </span>
                      <select class="form-control" name="product-unit">
                        <option value="">Select a unit</option>
                        <option value="pcs" <?php echo (!empty($product['unit']) && $product['unit'] === 'pcs') ? 'selected' : ''; ?>>Pieces (pcs)</option>
                        <option value="box" <?php echo (!empty($product['unit']) && $product['unit'] === 'box') ? 'selected' : ''; ?>>Box</option>
                        <option value="kg" <?php echo (!empty($product['unit']) && $product['unit'] === 'kg') ? 'selected' : ''; ?>>Kilogram (kg)</option>
                        <option value="ltr" <?php echo (!empty($product['unit']) && $product['unit'] === 'ltr') ? 'selected' : ''; ?>>Liter (ltr)</option>
                      </select>
                    </div>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php if($has_serial || $has_receipt): ?>
              <div class="form-group">
                <div class="row">
                  <?php if($has_serial): ?>
                  <div class="col-md-6">
                    <label for="product-serial">Serial Number</label>
                    <input type="text" class="form-control" name="product-serial" value="<?php echo isset($product['serial_number']) ? remove_junk($product['serial_number']) : ''; ?>">
                  </div>
                  <?php endif; ?>
                  <?php if($has_receipt): ?>
                  <div class="col-md-6">
                    <label for="product-receipt">Receipt Number</label>
                    <input type="text" class="form-control" name="product-receipt" value="<?php echo isset($product['receipt_number']) ? remove_junk($product['receipt_number']) : ''; ?>">
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>
              <button type="submit" name="product" class="btn btn-danger">Update</button>
          </form>
         </div>
        </div>
      </div>
  </div>

<?php include_once('layouts/footer.php'); ?>