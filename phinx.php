<?php

use DigraphCMS\Config;
use DigraphCMS\DB\DB;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__.'/initialize.php';

return
    [
        'paths' => [
            'migrations' => DB::migrationPaths(),
            'seeds' => DB::seedPaths(),
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'current',
            'current' => [
                'name' => Config::get('db.name'),
                'connection' => DB::pdo()
            ]
        ],
        'version_order' => 'creation',
    ];
