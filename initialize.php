<?php

/**
 * This script's purpose is to be included in other scripts and provide a single
 * point of configuration for core system settings. This is where all DB and
 * config options should be set.
 */

use DigraphCMS\Cache\CacheableState;
use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\Media\Media;
use DigraphCMS\Plugins\Plugins;
use DigraphCMS\UI\Templates;

// load plugins from composer
Plugins::loadFromComposer(__DIR__ . '/composer.lock');
Plugins::loadFromDirectory(__DIR__ . '/plugins');

// add site-specific directories
Templates::addSource(__DIR__ . '/templates');
Router::addSource(__DIR__ . '/routes');
Media::addSource(__DIR__ . '/media');

// initialize config
CachedInitializer::run(
    'initialization',
    function (CacheableState $state) {
        $state->mergeConfig(Config::parseYamlFile(__DIR__ . '/config.yaml'), true);
        $state->mergeConfig(Config::parseYamlFile(__DIR__ . '/env.yaml'), true);
        $state->config('paths.base', __DIR__);
    }
);

// set timezone
date_default_timezone_set(Config::get('theme.timezone'));
