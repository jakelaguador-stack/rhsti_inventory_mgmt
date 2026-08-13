<?php
  require_once('includes/load.php');
  if (!$session->isUserLoggedIn(true)) { redirect('index.php', false);}
?>

<?php
 // Auto suggetion
    $html = '';
   if(isset($_POST['product_name']) && strlen($_POST['product_name']))
   {
     $products = find_product_by_title($_POST['product_name']);
     if($products){
        foreach ($products as $product):
           $name = remove_junk($product['name']);
           $html .= "<li class=\"list-group-item\" data-id=\"{$product['id']}\">";
           $html .= $name;
           $html .= "</li>";
         endforeach;
      } else {
        $html .= '<li class="list-group-item text-muted">No matching products found</li>';
      }

      echo $html;
    exit;
   }
 ?>
 <?php
 // find all product
  if((isset($_POST['p_id']) && strlen($_POST['p_id'])) || (isset($_POST['p_name']) && strlen($_POST['p_name'])))
  {
    if(isset($_POST['p_id']) && strlen($_POST['p_id'])){
      $product_id = (int) $_POST['p_id'];
      $result = find_by_id('products', $product_id);
      $results = $result ? array($result) : false;
    } else {
      $product_title = remove_junk($db->escape($_POST['p_name']));
      $results = find_all_product_info_by_title($product_title);
    }
    if($results){
        foreach ($results as $result) {
          $product_name = remove_junk($result['name']);
          $buy_price = number_format((float)$result['buy_price'], 2, '.', '');
          $quantity = (int)$result['quantity'];
          $used = isset($result['used']) ? (int)$result['used'] : 0;
          $available = max(0, $quantity - $used);
          $unit = !empty($result['unit']) ? remove_junk($result['unit']) : 'piece';

          $html .= "<tr>";
          $html .= "<td id=\"s_name\">{$product_name}"
                 ."<input type=\"hidden\" name=\"s_id\" value=\"{$result['id']}\">"
                 ."<input type=\"hidden\" id=\"s_available\" value=\"{$available}\">"
                 ."</td>";
          $html .= "<td>";
          $html .= "<input type=\"number\" class=\"form-control\" name=\"price\" value=\"{$buy_price}\" step=\"0.01\" required>";
          $html .= "</td>";
          $html .= "<td id=\"s_qty\">";
          $html .= "<input type=\"number\" class=\"form-control\" name=\"quantity\" value=\"1\" min=\"1\" required>";
          $html .= "</td>";
          $html .= "<td>";
          $html .= "<input type=\"text\" class=\"form-control\" name=\"total\" value=\"{$buy_price}\" readonly>";
          $html .= "</td>";
          $html .= "<td class=\"text-center\">{$unit}</td>";
          $html .= "<td>";
          $html .= "<input type=\"date\" class=\"form-control datepicker\" name=\"date\" value=\"".date('Y-m-d')."\" required>";
          $html .= "</td>";
          $html .= "<td>";
          $html .= "<button type=\"submit\" name=\"add_sale\" class=\"btn btn-primary\">Get item</button>";
          $html .= "</td>";
          $html .= "</tr>";
        }
    } else {
        $html ='<tr><td colspan="7" class="text-center text-danger">Product name not registered in database.</td></tr>';
    }

    echo $html;
    exit;
  }
 ?>

