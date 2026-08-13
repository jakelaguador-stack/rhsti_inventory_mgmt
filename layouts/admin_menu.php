<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<ul>
  <li>
    <a href="admin.php">
      <i class="glyphicon glyphicon-home"></i>
      <span>Dashboard</span>
    </a>
  </li>
  <li>
    <a href="#" class="submenu-toggle">
      <i class="glyphicon glyphicon-user"></i>
      <span>User Management</span>
    </a>
    <ul class="nav submenu">
      <li><a href="group.php">Manage Groups</a> </li>
      <li><a href="users.php">Manage Users</a> </li>
   </ul>
  </li>
  <li>
    <a href="categorie.php" >
      <i class="glyphicon glyphicon-indent-left"></i>
      <span>Categories</span>
    </a>
  </li>
  <li>
    <a href="#" class="submenu-toggle">
      <i class="glyphicon glyphicon-th-large"></i>
      <span>Items</span>
    </a>
    <ul class="nav submenu">
       <li><a href="product_list.php">Items List</a> </li>
       <li><a href="add_product.php">Add Items</a> </li>

       <li class="submenu-product-list" style="padding:8px 12px;">
         <div style="max-height:220px; overflow:auto; font-size:13px;">
           <ul style="list-style:none; padding:0; margin:0;">
            <?php if(!empty($sidebar_products)): ?>
              <?php $sp = array_slice($sidebar_products, 0, 8); foreach($sp as $p): ?>
                 <li style="display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid #f5f5f5;">
                   <a href="edit_product.php?id=<?php echo (int)$p['id']; ?>" style="color:inherit; text-decoration:none; flex:1; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;">
                     <?php echo htmlspecialchars(first_character($p['name'])); ?>
                   </a>
                   <span style="margin-left:8px; color:#555; min-width:40px; text-align:right;">
                     <?php $used = isset($p['used']) ? (int)$p['used'] : 0; $qty = (int)$p['quantity']; $avail = max(0, $qty - $used); echo $avail; ?>
                   </span>
                 </li>
              <?php endforeach; ?>
              <?php if(count($sidebar_products) > 8): ?>
                <li style="padding-top:6px;"><a href="product_list.php">View all products...</a></li>
              <?php endif; ?>
            <?php endif; ?>
           </ul>
         </div>
       </li>

       <li><a href="add_sale.php">Get Items</a> </li>
       <li><a href="stock_list.php">Stock List</a> </li>
       <li><a href="used_items.php">Used Items</a> </li>
       <li><a href="product_report.php">Item Report</a> </li>
    </ul>
  </li>
  <li>
    <a href="media.php" >
      <i class="glyphicon glyphicon-picture"></i>
      <span>Media Files</span>
    </a>
  </li>
</ul>
