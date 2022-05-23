<?php

use DigraphCMS\Cron\Cron;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../initialize.php';

set_time_limit(300);
Cron::runJobs(time() + 60);
