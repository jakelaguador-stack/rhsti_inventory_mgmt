<?php
  $page_title = 'Add Product';
  require_once('includes/load.php');
  // Check kung anong level ng user ang may permission na makita ito
  page_require_level(2);
  $all_categories = find_all('categories');
  $products = join_product_table();
?>
<?php
 if(isset($_POST['add_product'])){
   $req_fields = array('product-title','product-categorie','product-quantity','buying-price','product-date');
   if (function_exists('columnExists') && columnExists('products', 'unit')) {
     $req_fields[] = 'product-unit';
   }
   validate_fields($req_fields);
   if(empty($errors)){
     $p_name  = remove_junk($db->escape($_POST['product-title']));
     $p_cat   = remove_junk($db->escape($_POST['product-categorie']));
     $p_qty   = (int)remove_junk($db->escape($_POST['product-quantity']));
     $p_buy   = remove_junk($db->escape($_POST['buying-price']));
     $p_unit  = remove_junk($db->escape($_POST['product-unit']));
     
     if(!is_numeric($p_qty) || $p_qty < 1){
       $errors[] = 'Quantity must be a positive number.';
     }
     if(!is_numeric($p_buy) || (float)$p_buy <= 0){
       $errors[] = 'Price must be a positive number.';
     }
     if($p_unit === ''){
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

       $query  = "INSERT INTO products (";
       $query .=" name, quantity, unit, buy_price, categorie_id, media_id, date";
       $query .=") VALUES (";
       $query .=" '{$p_name}', '{$p_qty}', '{$p_unit}', '{$p_buy}', '{$p_cat}', '{$media_id}', '{$p_date}'";
       $query .=")";
       
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
                  <input type="text" class="form-control" name="product-title" placeholder="Product Title" required>
               </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-md-6">
                    <select class="form-control" name="product-categorie" required>
                      <option value="">Select Product Category</option>
                    <?php  foreach ($all_categories as $cat): ?>
                      <option value="<?php echo (int)$cat['id'] ?>">
                        <?php echo $cat['name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="product-photo" style="display:block; color:#888; margin-bottom:5px;">Upload Product Image</label>
                      <label for="product-photo" style="cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:120px; height:120px; border:2px dashed #ccc; background:#fafafa; color:#666; font-size:36px;">
                        <span class="glyphicon glyphicon-plus"></span>
                      </label>
                      <input type="file" id="product-photo" name="product-photo" accept="image/*" style="display:none;">
                      <p class="help-block" style="margin:5px 0 0;">Click the plus sign to choose an image.</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group">
               <div class="row">
                 <div class="col-md-6">
                   <label for="product-unit" style="display:block; color:#888; margin-bottom:5px;">Unit</label>
                   <div class="row" style="margin:0;">
                     <div class="col-xs-8" style="padding-left:0;">
                       <div class="input-group" style="width:100%;">
                         <span class="input-group-addon" style="width: auto;">
                           <i class="glyphicon glyphicon-shopping-cart"></i>
                         </span>
                         <input type="number" class="form-control" name="product-quantity" placeholder="Product Quantity" min="1" step="1" required style="border-top-left-radius:0; border-bottom-left-radius:0;">
                       </div>
                     </div>
                     <div class="col-xs-4" style="padding-right:0;">
                       <select id="product-unit" class="form-control" name="product-unit" style="display:block; width:100%; cursor:pointer; min-height:42px; position:relative; z-index:2; background-color:#fff;" required>
                         <option value="">Unit</option>
                         <option value="piece">Piece</option>
                         <option value="pack">Pack</option>
                         <option value="box">Box</option>
                       </select>
                     </div>
                   </div>
                 </div>
                 <div class="col-md-6">
                   <div class="input-group">
                     <span class="input-group-addon">PHP</span>
                     <input type="number" class="form-control" name="buying-price" placeholder="Buying Price" min="0.01" step="0.01" required>
                     <span class="input-group-addon">.00</span>
                  </div>
                 </div>
               </div>
              </div>
              <div class="form-group">
               <div class="row">
                 <div class="col-md-6">
                   <div class="input-group">
                     <span class="input-group-addon">
                       <i class="glyphicon glyphicon-calendar"></i>
                     </span>
                     <input type="datetime-local" class="form-control" name="product-date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                  </div>
                 </div>
               </div>
              </div>
              
              <div style="display: inline-flex; gap: 5px;">
                <button type="submit" name="add_product" class="btn btn-danger">Add Product</button>
                <a href="add_sale.php" class="btn btn-warning">Get Items</a>
              </div>
              
          </form>
         </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <strong>
            <span class="glyphicon glyphicon-th-list"></span>
            <span>Product List</span>
          </strong>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
              <thead>
                <tr>
                  <th class="text-center" style="width: 50px;">#</th>
                  <th>Product Name</th>
                  <th>Category</th>
                  <th class="text-center">Total Quantity</th>
                  <th class="text-center">Buy Price / Unit</th>
                  <th class="text-center">Image</th>
                  <th class="text-center">Date</th>
                  <th class="text-center">Available Items</th>
                  <th class="text-center">Used Items</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($products)): ?>
                  <tr>
                    <td colspan="10" class="text-center">No products found.</td>
                  </tr>
                <?php else: ?>
                  <?php $count = 1; ?>
                  <?php foreach($products as $product): ?>
                    <?php 
                      $used = isset($product['used']) ? (int)$product['used'] : 0;
                      $quantity = (int)$product['quantity'];
                      $available = max(0, $quantity - $used);
                    ?>
                    <tr>
                      <td class="text-center"><?php echo $count++; ?></td>
                      <td><?php echo remove_junk(first_character($product['name'])); ?></td>
                      <td><?php echo remove_junk(first_character($product['categorie'])); ?></td>
                      
                      <td class="text-center" style="white-space: nowrap;">
                        <strong><?php echo $quantity; ?></strong> 
                        <span class="label label-default" style="font-size: 11px; margin-left: 3px;">
                          <?php echo !empty($product['unit']) ? remove_junk($product['unit']) : 'piece'; ?>
                        </span>
                      </td>

                      <td class="text-center">
                        PHP <?php echo number_format((float)$product['buy_price'], 2); ?> / <?php echo !empty($product['unit']) ? remove_junk($product['unit']) : 'piece'; ?>
                      </td>
                      <td class="text-center">
                        <?php if($product['media_id'] === '0'): ?>
                          <img src="uploads/products/no_image.png" alt="No Image" style="height:30px;" />
                        <?php else: ?>
                          <img src="uploads/products/<?php echo $product['image']; ?>" alt="Product Image" style="height:30px;" />
                        <?php endif; ?>
                      </td>
                      <td class="text-center"><?php echo read_date($product['date']); ?></td>
                      <td class="text-center"><span class="label label-success" style="font-size: 12px;"><?php echo $available; ?></span></td>
                      <td class="text-center"><span class="label label-warning" style="font-size: 12px;"><?php echo $used; ?></span></td>
                      <td class="text-center">
                        <div class="btn-group" role="group">
                          <a href="add_sale.php?id=<?php echo (int)$product['id']; ?>" class="btn btn-sm btn-warning">Get Item</a>
                          <a href="edit_product.php?id=<?php echo (int)$product['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                          <a href="delete_product.php?id=<?php echo (int)$product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                        </div>
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
  </div>

<?php include_once('layouts/footer.php'); ?>