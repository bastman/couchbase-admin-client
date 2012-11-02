<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/1/12
 * Time: 1:03 PM
 * To change this template use File | Settings | File Templates.
 */

// ========== bootstrap ======
require __DIR__ . "/../../../../vendor/autoload.php";

//require_once dirname(__FILE__) . '/../Bootstrap.php';

use TestStackExample\Bootstrap;
use TestStackExample\Task\Runner;

Bootstrap::getInstance()
    ->init();

// ========== run ============

Runner::getInstance()->run();
