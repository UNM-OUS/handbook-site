<?php

use DigraphCMS\Cron\Cron;

// get site name
$siteName = basename(realpath(__DIR__ . '/../..'));
$backupDir = '/home/handbook/public_html/_backup';

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../initialize.php';
    set_time_limit(300);
    Cron::runJobs(time() + 60);
} catch (\Throwable $th) {
    echo "$siteName: cron: " . $th->getMessage();
}
