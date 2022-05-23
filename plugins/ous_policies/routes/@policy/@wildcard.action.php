<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;

// verify that uuid looks right
$prefix = preg_replace('/_.*$/', '', Context::url()->action());
if (is_file(__DIR__ . '/_wildcard_handlers/' . $prefix . '.php')) {
    include __DIR__ . '/_wildcard_handlers/' . $prefix . '.php';
} else {
    throw new HttpError(404);
}
