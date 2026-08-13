<?php
  require_once('includes/load.php');

/*--------------------------------------------------------------*/
/* Function for find all database table rows by table name
/*--------------------------------------------------------------*/
function find_all($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM ".$db->escape($table));
   }
}
/*--------------------------------------------------------------*/
/* Function for Perform queries
/*--------------------------------------------------------------*/
function find_by_sql($sql)
{
  global $db;
  $result = $db->query($sql);
  if (!$result) {
    return array();
  }
  $result_set = $db->while_loop($result);
  return $result_set;
}
/*--------------------------------------------------------------*/
/*  Function for Find data from table by id
/*--------------------------------------------------------------*/
function find_by_id($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE id='{$db->escape($id)}' LIMIT 1");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
/*--------------------------------------------------------------*/
/* Function for Delete data from table by id
/*--------------------------------------------------------------*/
function delete_by_id($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE id=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}
/*--------------------------------------------------------------*/
/* Function for Count id  By table name
/*--------------------------------------------------------------*/

function count_by_id($table){
  global $db;
  if(tableExists($table))
  {
    $sql    = "SELECT COUNT(id) AS total FROM ".$db->escape($table);
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
  }
}
/*--------------------------------------------------------------*/
/* Determine if database table exists
/*--------------------------------------------------------------*/
function tableExists($table){
  global $db;
  $table_exit = $db->query('SHOW TABLES FROM '.DB_NAME.' LIKE "'.$db->escape($table).'"');
      if($table_exit) {
        if($db->num_rows($table_exit) > 0)
              return true;
         else
              return false;
      }
  }
 /*--------------------------------------------------------------*/
 /* Login with the data provided in $_POST,
 /* coming from the login form.
/*--------------------------------------------------------------*/
  function authenticate($username='', $password='') {
    global $db;
    $username = $db->escape($username);
    $password = $db->escape($password);
    $sql  = sprintf("SELECT id,username,password,user_level FROM users WHERE username ='%s' LIMIT 1", $username);
    $result = $db->query($sql);
    if($db->num_rows($result)){
      $user = $db->fetch_assoc($result);
      $password_request = sha1($password);
      if($password_request === $user['password'] ){
        return $user['id'];
      }
    }
   return false;
  }
  /*--------------------------------------------------------------*/
  /* Login with the data provided in $_POST,
  /* coming from the login_v2.php form.
  /* If you used this method then remove authenticate function.
 /*--------------------------------------------------------------*/
   function authenticate_v2($username='', $password='') {
     global $db;
     $username = $db->escape($username);
     $password = $db->escape($password);
     $sql  = sprintf("SELECT id,username,password,user_level FROM users WHERE username ='%s' LIMIT 1", $username);
     $result = $db->query($sql);
     if($db->num_rows($result)){
       $user = $db->fetch_assoc($result);
       $password_request = sha1($password);
       if($password_request === $user['password'] ){
         return $user;
       }
     }
    return false;
   }


  /*--------------------------------------------------------------*/
  /* Find current log in user by session id
  /*--------------------------------------------------------------*/
  function current_user(){
      static $current_user;
      global $db;
      if(!$current_user){
         if(isset($_SESSION['user_id'])):
             $user_id = intval($_SESSION['user_id']);
             $current_user = find_by_id('users',$user_id);
        endif;
      }
    return $current_user;
  }
  /*--------------------------------------------------------------*/
  /* Find all user by
  /* Joining users table and user gropus table
  /*--------------------------------------------------------------*/
  function find_all_user(){
      global $db;
      $results = array();
      $sql = "SELECT u.id,u.name,u.username,u.user_level,u.status,u.last_login,";
      $sql .="g.group_name ";
      $sql .="FROM users u ";
      $sql .="LEFT JOIN user_groups g ";
      $sql .="ON g.group_level=u.user_level ORDER BY u.name ASC";
      $result = find_by_sql($sql);
      return $result;
  }
  /*--------------------------------------------------------------*/
  /* Function to update the last log in of a user
  /*--------------------------------------------------------------*/

 function updateLastLogIn($user_id)
	{
		global $db;
    $date = make_date();
    $sql = "UPDATE users SET last_login='{$date}' WHERE id ='{$user_id}' LIMIT 1";
    $result = $db->query($sql);
    return ($result && $db->affected_rows() === 1 ? true : false);
	}

  /*--------------------------------------------------------------*/
  /* Find all Group name
  /*--------------------------------------------------------------*/
  function find_by_groupName($val)
  {
    global $db;
    $sql = "SELECT group_name FROM user_groups WHERE group_name = '{$db->escape($val)}' LIMIT 1 ";
    $result = $db->query($sql);
    return($db->num_rows($result) === 0 ? true : false);
  }
  /*--------------------------------------------------------------*/
  /* Find group level
  /*--------------------------------------------------------------*/
  function find_by_groupLevel($level)
  {
    global $db;
    $sql = "SELECT group_level FROM user_groups WHERE group_level = '{$db->escape($level)}' LIMIT 1 ";
    $result = $db->query($sql);
    return($db->num_rows($result) === 0 ? true : false);
  }
  /*--------------------------------------------------------------*/
  /* Function for cheaking which user level has access to page
  /*--------------------------------------------------------------*/
   function page_require_level($require_level){
     global $session;
     $current_user = current_user();
     $login_level = find_by_groupLevel($current_user['user_level']);
     //if user not login
     if (!$session->isUserLoggedIn(true)):
            $session->msg('d','Please login...');
            redirect('index.php', false);
      //if Group status Deactive
     elseif($login_level['group_status'] === '0'):
           $session->msg('d','This level user has been band!');
           redirect('home.php',false);
      //cheackin log in User level and Require level is Less than or equal to
     elseif($current_user['user_level'] <= (int)$require_level):
              return true;
      else:
            $session->msg("d", "Sorry! you dont have permission to view the page.");
            redirect('home.php', false);
        endif;

     }
   /*--------------------------------------------------------------*/
   /* Function for Finding all product name
   /* JOIN with categorie  and media database table
   /*--------------------------------------------------------------*/
    function join_product_table(){
      global $db;
      $has_unit = function_exists('columnExists') && columnExists('products', 'unit');
      $has_serial = function_exists('columnExists') && columnExists('products', 'serial_number');
      $has_receipt = function_exists('columnExists') && columnExists('products', 'receipt_number');

      $sql  = " SELECT p.id,p.name,p.quantity,COALESCE(p.used,0) AS used";
      $sql .= $has_unit ? ", p.unit" : ", '' AS unit";
      $sql .= ", p.buy_price,p.media_id,p.date";
      $sql .= $has_serial ? ", p.serial_number" : ", '' AS serial_number";
      $sql .= $has_receipt ? ", p.receipt_number" : ", '' AS receipt_number";
      $sql .= ", c.name AS categorie, m.file_name AS image";
      $sql .= " FROM products p";
      $sql .= " LEFT JOIN categories c ON c.id = p.categorie_id";
      $sql .= " LEFT JOIN media m ON m.id = p.media_id";
      $sql .= " ORDER BY p.id ASC";
      return find_by_sql($sql);

    }

    function ensure_product_used_column(){
      global $db;
      $result = $db->query("SHOW COLUMNS FROM products LIKE 'used'");
      if($result && $db->num_rows($result) === 0){
        $db->query("ALTER TABLE products ADD COLUMN used INT(11) NOT NULL DEFAULT 0 AFTER quantity");
      }
    }
  /*--------------------------------------------------------------*/
  /* Function for Finding all product name
  /* Request coming from ajax.php for auto suggest
  /*--------------------------------------------------------------*/

   function find_product_by_title($product_name){
     global $db;
     $p_name = remove_junk($db->escape($product_name));
     $sql = "SELECT id, name FROM products WHERE name LIKE '%$p_name%' LIMIT 5";
     $result = find_by_sql($sql);
     return $result;
   }

  /*--------------------------------------------------------------*/
  /* Function for Finding all product info by product title
  /* Request coming from ajax.php
  /*--------------------------------------------------------------*/
  function find_all_product_info_by_title($title){
    global $db;
    $title = remove_junk($db->escape($title));
    $sql  = "SELECT * FROM products ";
    $sql .= " WHERE name LIKE '%{$title}%'";
    $sql .=" LIMIT 1";
    return find_by_sql($sql);
  }

  /*--------------------------------------------------------------*/
  /* Function for Update product quantity
  /*--------------------------------------------------------------*/
  function update_product_used($qty,$p_id){
    global $db;
    $qty = (int) $qty;
    $id  = (int)$p_id;
    $sql = "UPDATE products SET used = GREATEST(0, COALESCE(used, 0) + '{$qty}') WHERE id = '{$id}'";
    $result = $db->query($sql);
    return ($result && $db->affected_rows() === 1 ? true : false);
  }

  function adjust_product_used($new_qty, $old_qty, $p_id){
    global $db;
    $delta = (int)$new_qty - (int)$old_qty;
    $id = (int)$p_id;
    if($delta === 0){
      return true;
    }
    $sql = "UPDATE products SET used = GREATEST(0, COALESCE(used, 0) + '{$delta}') WHERE id = '{$id}'";
    $result = $db->query($sql);
    return ($result && $db->affected_rows() === 1 ? true : false);
  }
  /*--------------------------------------------------------------*/
  /* Function for Display Recent product Added
  /*--------------------------------------------------------------*/
 function find_recent_product_added($limit){
   global $db;
   // sale_price column was removed from DB; alias buy_price as sale_price so UI that
   // expects 'sale_price' continues to work.
   $sql   = " SELECT p.id,p.name,p.buy_price AS sale_price,p.media_id,c.name AS categorie,";
   $sql  .= "m.file_name AS image FROM products p";
   $sql  .= " LEFT JOIN categories c ON c.id = p.categorie_id";
   $sql  .= " LEFT JOIN media m ON m.id = p.media_id";
   $sql  .= " ORDER BY p.id DESC LIMIT ". $db->escape((int)$limit);
   return find_by_sql($sql);
 }
 /*--------------------------------------------------------------*/
 /* Function for Find top used products
 /*--------------------------------------------------------------*/
 function find_most_used_products($limit){
   global $db;
   $sql  = "SELECT p.id, p.name, COALESCE(p.used,0) AS totalUsed, c.name AS categorie, m.file_name AS image";
   $sql .= " FROM products p";
   $sql .= " LEFT JOIN categories c ON c.id = p.categorie_id";
   $sql .= " LEFT JOIN media m ON m.id = p.media_id";
   $sql .= " ORDER BY COALESCE(p.used,0) DESC LIMIT ".$db->escape((int)$limit);
   return find_by_sql($sql);
 }

/*--------------------------------------------------------------*/
/* Function for find all get item records (deprecated)
/*--------------------------------------------------------------*/
function find_all_get_item_records(){
  return array();
}

/*--------------------------------------------------------------*/
/* Function for Display Recent get item updates
/*--------------------------------------------------------------*/
function find_recent_request_added($limit){
  global $db;
  $sql  = "SELECT p.id, p.name, COALESCE(p.used,0) AS qty, p.buy_price AS price, p.date";
  $sql .= " FROM products p";
  $sql .= " ORDER BY p.date DESC LIMIT ".$db->escape((int)$limit);
  return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Function for sum of all used items
/*--------------------------------------------------------------*/
function total_used_items(){
  global $db;
  $sql = "SELECT SUM(COALESCE(used,0)) AS total FROM products";
  $result = $db->query($sql);
  return $result ? $db->fetch_assoc($result) : array('total' => 0);
}

/*--------------------------------------------------------------*/
/* Function for Generate get item summary report by current product data
/*--------------------------------------------------------------*/
function find_sale_by_dates($start_date,$end_date){
  global $db;
  $start_date  = date("Y-m-d", strtotime($start_date));
  $end_date    = date("Y-m-d", strtotime($end_date));
  $sql  = "SELECT p.name, COALESCE(p.used,0) AS used, p.buy_price, p.date";
  $sql .= " FROM products p";
  $sql .= " WHERE DATE(p.date) BETWEEN '{$start_date}' AND '{$end_date}'";
  $sql .= " ORDER BY p.date DESC";
  return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Function for Generate Daily request report from current product data
/*--------------------------------------------------------------*/
function dailyRequests($year,$month){
  global $db;
  $sql  = "SELECT COALESCE(p.used,0) AS qty, DATE_FORMAT(p.date, '%Y-%m-%e') AS date, p.name";
  $sql .= " FROM products p";
  $sql .= " WHERE DATE_FORMAT(p.date, '%Y-%m' ) = '{$year}-{$month}'";
  $sql .= " ORDER BY p.date ASC";
  return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Function for Generate Monthly request report from current product data
/*--------------------------------------------------------------*/
function monthlyRequests($year){
  global $db;
  $sql  = "SELECT COALESCE(p.used,0) AS qty, DATE_FORMAT(p.date, '%Y-%m-%e') AS date, p.name, ";
  $sql .= "(COALESCE(p.used,0) * COALESCE(p.buy_price,0)) AS total_saleing_price";
  $sql .= " FROM products p";
  $sql .= " WHERE DATE_FORMAT(p.date, '%Y' ) = '{$year}'";
  $sql .= " ORDER BY DATE_FORMAT(p.date, '%c' ) ASC";
  return find_by_sql($sql);
}

?>
