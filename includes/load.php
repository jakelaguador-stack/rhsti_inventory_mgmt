
<?php
// -----------------------------------------------------------------------
// DEFINE SEPERATOR ALIASES
// -----------------------------------------------------------------------
define("URL_SEPARATOR", '/');

define("DS", DIRECTORY_SEPARATOR);

// -----------------------------------------------------------------------
// SET APPLICATION TIMEZONE TO PHILIPPINES
// -----------------------------------------------------------------------
if (!function_exists('date_default_timezone_set')) {
  exit('PHP timezone support is required.');
}
date_default_timezone_set('Asia/Manila');

// -----------------------------------------------------------------------
// DEFINE ROOT PATHS
// -----------------------------------------------------------------------
defined('SITE_ROOT')? null: define('SITE_ROOT', realpath(dirname(__FILE__)));
define("LIB_PATH_INC", SITE_ROOT.DS);


require_once(LIB_PATH_INC.'config.php');
require_once(LIB_PATH_INC.'functions.php');
require_once(LIB_PATH_INC.'session.php');
require_once(LIB_PATH_INC.'upload.php');
require_once(LIB_PATH_INC.'database.php');
require_once(LIB_PATH_INC.'sql.php');

if(function_exists('ensure_product_name_index_is_not_unique')){
  ensure_product_name_index_is_not_unique();
}
if(function_exists('ensure_product_used_column')){
  ensure_product_used_column();
}
if(function_exists('ensure_product_unit_column')){
  ensure_product_unit_column();
}
if(function_exists('ensure_product_serial_receipt_columns')){
  ensure_product_serial_receipt_columns();
}
if(function_exists('ensure_product_remarks_column')){
  ensure_product_remarks_column();
}

?>

