<?php

/**
 * This script is designed to be run from the command line, triggered by GitHub
 * workflows. It will look in the _backup directory in public_html and restore
 * the most recent backup for this site found there.
 */

use DigraphCMS\Config;
use DigraphCMS\DB\DB;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../initialize.php';

// only works on mysql
if (Config::get('db.adapter') !== 'mysql') {
    throw new \Exception("This script only works for MySQL databases");
}

// save password so we can use it after DB clears it
$pass = Config::get('db.pass');

// drop all tables
$pdo = DB::pdo();
$pdo->beginTransaction();
$pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
foreach ($pdo->query('SHOW TABLES')->fetchAll() as $r) {
    $query = $pdo->prepare('DROP TABLE ' . $r[0]);
    if ($query->execute()) {
        echo "Dropped table " . $r[0] . PHP_EOL;
    } else {
        throw new \Exception("Error dropping table " . $r[0] . ': ' . $query->errorInfo());
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
$pdo->commit();

// also depends on having exec available
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
    $pass,
    Config::get('db.host')
));
exec(
    sprintf(
        'zcat %s | mysql %s',
        $file = latestBackup(),
        implode(' ', [
            // config from file
            '--defaults-extra-file=' . $mysqlCnf,
            // db name from command line
            '--database=' . Config::get('db.name')
        ])
    ),
    $output,
    $result
);
unlink($mysqlCnf);

// throw error if result is non-zero
if ($result !== 0) {
    throw new \Exception("Failed to restore database");
}

// otherwise echo message
echo "Restored database from " . basename($file);
echo PHP_EOL;

// function for locating latest backup file
function latestBackup(): string
{
    $siteName = basename(realpath(Config::get('paths.base') . '/..'));
    $files = glob(Config::get('paths.base') . '/../../_backup/' . $siteName . '/*.sql.gz');
    if (!$files) {
        throw new \Exception("No backup files found to restore from");
    }
    asort($files);
    return end($files);
}
