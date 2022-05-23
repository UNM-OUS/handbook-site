<?php

/**
 * This script is designed to be run from the command line, triggered by GitHub
 * workflows. It will connect to the database using exec() to run mysqldump and
 * drop the entire database into a compressed gz file in a folder named "backup"
 * one level above this repo's root directory.
 */

use DigraphCMS\Config;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../initialize.php';

// only works on mysql
if (Config::get('db.adapter') !== 'mysql') {
    throw new \Exception("This script only works for MySQL databases");
}

// also depends on having exec available
$siteName = basename(realpath(Config::get('paths.base') . '/..'));
// put config into a cnf file
$mysqlCnf = Config::get('paths.base') . '/cache/mysql.cnf';
file_put_contents($mysqlCnf, sprintf(
    implode(PHP_EOL, [
        '[client]',
        'user = "%s"',
        'password = "%s"',
        'host = "%s"'
    ]),
    Config::get('db.user'),
    Config::get('db.pass'),
    Config::get('db.host')
));
exec(
    sprintf(
        'mysqldump %s %s | gzip > %s',
        implode(' ', [
            // config from file
            '--defaults-extra-file=' . $mysqlCnf,
            // dumps a single consistent state without blocking, only works with InnoDB
            '--single-transaction',
            '--quick',
            '--add-drop-table',
            '--add-locks',
            '--create-options',
            '--disable-keys',
            '--extended-insert',
            '--set-charset',
            '--no-tablespaces',
        ]),
        Config::get('db.name'),
        $file = Config::get('paths.base') . '/../../_backup/' . $siteName . '/' . date('Ymd_His') . '.sql.gz'
    ),
    $output,
    $result
);
unlink($mysqlCnf);

// throw exception on error
if ($result !== 0) {
    throw new \Exception("Failed to back up database");
}

// echo confirmation
echo "Backed up database to " . basename($file);
echo PHP_EOL;
