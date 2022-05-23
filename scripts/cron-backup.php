<?php

use DigraphCMS\Config;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../initialize.php';

// get site name
$siteName = basename(realpath(Config::get('paths.base') . '/..'));
$backupDir = '/home/univsec/public_html/_backup';

// clear old backups
$backups = glob("$backupDir/$siteName/*");
foreach ($backups as $file) {
    if (!is_dir($file) && filemtime($file) < time() - (86400 * 14)) {
        unlink($file);
    }
}

// enter maintenance mode
touch(__DIR__ . '/../.maintenance');

// back up production database
$script = Config::get('paths.base') . '/scripts/db-backup.php';
exec("/opt/cpanel/ea-php74/root/usr/bin/php $script");

// back up production storage
$base = Config::get('paths.base');
$filename = date('Ymd_His');
$filename = "$backupDir/$siteName/$filename.zip";
exec("cd $base && zip -ruq $filename storage");

// leave maintenance mode
unlink(__DIR__ . '/../.maintenance');
