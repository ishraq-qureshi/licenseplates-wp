<?php
/**
 * @package Configuration Settings
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * File Built by Zen Cart Installer on Fri Oct 12 2018 01:25:38
 */

/*************** NOTE: This file is VERY similar to, but DIFFERENT from the "admin" version of configure.php. ***********/
/***************       The 2 files should be kept separate and not used to overwrite each other.              ***********/

/**
 * Enter the domain for your store
 * HTTP_SERVER is your Main webserver: eg-http://www.yourdomain.com
 * HTTPS_SERVER is your Secure/SSL webserver: eg-https://www.yourdomain.com
 */
define('HTTP_SERVER', 'https://www.calplates.com');
define('HTTPS_SERVER', 'https://www.calplates.com');

/**
 *  If you want to tell Zen Cart to use your HTTPS URL on sensitive pages like login and checkout, set this to 'true'. Otherwise 'false'. (Keep the quotes)
 */
define('ENABLE_SSL', 'True');

/**
 * These DIR_WS_xxxx values refer to the name of any subdirectory in which your store is located.
 * These values get added to the HTTP_CATALOG_SERVER and HTTPS_CATALOG_SERVER values to form the complete URLs to your storefront.
 * They should always start and end with a slash ... ie: '/' or '/foldername/'
 */
define('DIR_WS_CATALOG', '/');
define('DIR_WS_HTTPS_CATALOG', '/');
define('DIR_WS_HTTPS_CATALOG_IMAGE', 'https://cdn.calplates.com/');
/**
 * This is the complete physical path to your store's files.  eg: /var/www/vhost/accountname/public_html/store/
 * Should have a closing / on it.
 */
define('DIR_FS_CATALOG', '/var/www/vhosts/calplates.com/httpdocs/');

/**
 * The following settings define your database connection.
 * These must be the SAME as you're using in your admin copy of configure.php
 */
define('DB_TYPE', 'mysql'); // always 'mysql'
define('DB_PREFIX', 'zen2_'); // prefix for database table names -- preferred to be left empty
define('DB_CHARSET', 'utf8'); // 'utf8' or 'latin1' are most common
define('DB_SERVER', 'localhost');  // address of your db server
define('DB_SERVER_USERNAME', 'ifarvej4_may');
define('DB_SERVER_PASSWORD', 'xMZ.4l;F9sev');
define('DB_DATABASE', 'ifarvej4_calplates');
 define('DIR_WS_ORDER_IMG', 'orders/');
  define('DIR_WS_CART_IMG', 'orders_cart/');
  define('DIR_WS_TEMP_IMG', 'orders_temp/');
  define('DIR_FS_ORDER_FILES', 'orders_files/');

  define('CUSTOM_REQUEST_ID', 8613);
  define('CUSTOM_REQUEST_CATEGORY_ID', 1066);

  define('ONESTEP_MODULE_SHIPPING', 'zones_zones');
  define('ONESTEP_MODULE_PAYMENT', 'authorizenet_aim');
  define('ADMIN_MODULE_PAYMENT', 'moneyorder');
  define('CUSTOMERS_APPROVAL_NO_AUTHORIZATION', 4);
/**
 * This is an advanced setting to determine whether you want to cache SQL queries.
 * Options are 'none' (which is the default) and 'file' and 'database'.
 */
define('SQL_CACHE_METHOD', 'none');

/**
 * Reserved for future use
 */
define('SESSION_STORAGE', 'reserved for future use');
define('SESSION_TIMEOUT_CATALOG',86400);
/**
 * Advanced use only:
 * The following are OPTIONAL, and should NOT be set unless you intend to change their normal use. Most sites will leave these untouched.
 * To use them, uncomment AND add a proper defined value to them.
 */
// define('DIR_FS_SQL_CACHE' ...
// define('DIR_FS_DOWNLOAD' ...
// define('DIR_FS_LOGS' ...

// End Of File
