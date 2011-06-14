<?php
/**
 * Copyright (c) 2010 Yahoo! Inc. All rights reserved. Copyrights licensed under the MIT License.
 *
 * User Interface
 *
 * @category Web
 * @package yJeopardy
 * @author Alex Ivashchenko <alexi@yahoo-inc.com>
 * @author Suresh Jayanty <jayantys@yahoo-inc.com>
 * @author St. John Johnson <stjohn@yahoo-inc.com>
 */

// Switch interfaces
switch (TRUE)
{
    case isset($_GET['iphone']):
        include 'iphone.html';
        exit;
    case isset($_GET['droid']):
        include 'droid.html';
        exit;
    case isset($_GET['ipad']):
        include 'ipad.html';
        exit;
    case isset($_GET['desktop']):
        include 'desktop.html';
        exit;
    case isset($_GET['alt']):
    case isset($_GET['simple']):
        include 'simple.html';
        exit;
    case isset($_GET['main']):
        include 'main.html';
        exit;
    case isset($_GET['admin']):
        include 'admin.php';
        exit;

    // switch user agents
    // iPhone,iPod
    case (preg_match('/\(iPhone;|\(iPod;/', $_SERVER['HTTP_USER_AGENT'])):
        include 'iphone.html';
        exit;
        break;

    // iPad
    case (preg_match('/\(iPad;/', $_SERVER['HTTP_USER_AGENT'])):
        include 'ipad.html';
        exit;
        break;

    // WebKit (Safari + Chrome) + Firefox
    case (preg_match('/Safari/', $_SERVER['HTTP_USER_AGENT'])):
    case (preg_match('/Firefox/', $_SERVER['HTTP_USER_AGENT'])):
        include 'desktop.html';
        exit;
        break;

    // Rest of us
    default:
        include 'simple.html';
        exit;
}
