<?php
/**
 * This file will only work if run through PHP's CLI server
 */

use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Digraph;

// Check if .maintenance exists, and if so only show maintenance page
if (is_file(__DIR__ . '/../.maintenance')) {
    include __DIR__ . '/../maintenance.php';
    exit();
}

// die if not using PHP server
if (php_sapi_name() !== 'cli-server') exit();

// load autoloader after maintenance check
require_once __DIR__ . "/../vendor/autoload.php";

// special cases for running in PHP's built-in server
$r = @reset(explode('?', $_SERVER['REQUEST_URI'], 2));
if ($r == '/favicon.ico' || substr($r, 0, 7) == '/files/') {
    return false;
}

// expiring initialization cache for development
CachedInitializer::configureCache(__DIR__ . '/../cache', 60);

// load initialization
require_once __DIR__ . '/../initialize.php';

// build and render response
Digraph::renderActualRequest();