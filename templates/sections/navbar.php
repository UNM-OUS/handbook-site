<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\UI\MenuBar\MenuBar;
use DigraphCMS\URL\URL;

$menu = (new MenuBar)
    ->setID('main-nav');
if ($home = Pages::get('home')) $menu->addPage($home, 'Home');
$menu->addURL(new URL('/policies/'), 'Policies');
$menu->addURL(new URL('/policy_updates/'), 'Updates');
$menu->addURL(new URL('/under_review/'), 'Under review');
$menu->addURL(new URL('/information/'), 'Information');
$menu->addURL(new URL('/resources/'), 'Resources');
$menu->addURL(new URL('/pdf/'), 'PDFs');
echo $menu;
