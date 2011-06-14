<?php
/**
 * Copyright (c) 2010 Yahoo! Inc. All rights reserved. Copyrights licensed under the MIT License.
 */
/* Configuration */
require_once $_SERVER['DOCUMENT_ROOT'].'/Config.php';

/**
 * Admin Interface
 *
 * @category Web
 * @package yJeopardy
 * @author Suresh Jayanty <jayantys@yahoo-inc.com>
 * @author St. John Johnson <stjohn@yahoo-inc.com>
 */

if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] != ADMIN_USERNAME ||
    $_SERVER['PHP_AUTH_PW'] != ADMIN_PASSWORD) {
  header('WWW-Authenticate: Basic realm="Y!Jeopardy Admin"');
  header('HTTP/1.0 401 Unauthorized');
  exit;
}
