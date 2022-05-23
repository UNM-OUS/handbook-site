<?php

use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Digraph;

// Check if .maintenance exists, and if so only show maintenance page
if (is_file(__DIR__ . '/../.maintenance')) {
    include __DIR__ . '/../maintenance.php';
    exit();
}

// load autoloader after maintenance check
require_once __DIR__ . "/../vendor/autoload.php";

// special case for running in PHP's built-in server
// sets a short initializer cache, so that there can be better cues about speed
if (php_sapi_name() === 'cli-server') {
    CachedInitializer::configureCache(__DIR__ . '/../cache', 60);
    $r = @reset(explode('?', $_SERVER['REQUEST_URI'], 2));
    if ($r == '/favicon.ico' || substr($r, 0, 7) == '/files/') {
        return false;
    }
}

// configure initialization cache outside built-in server
// because this only runs on the real servers and those have their caches
// cleared whenever deploys happen, the ttl can be infinite
else {
    CachedInitializer::configureCache(__DIR__ . '/../cache', -1);
}

require_once __DIR__ . '/../initialize.php';

// build and render response
Digraph::renderActualRequest();
