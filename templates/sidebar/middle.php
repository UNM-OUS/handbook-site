<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\UI\MenuBar\MenuBar;
use DigraphCMS\URL\URL;

echo Cache::get(
    'sidebar/' . md5(Context::url()->path()),
    function () {
        $menu = new MenuBar;
        $menu->addClass('menubar--vertical');
        $menu->addClass('menubar--manual-toggle');
        $menu->addClass('sidebar__menu');

        if ($page = Pages::get('section_a')) $menu->addPageDropdown($page, "Section A: The University", true);
        if ($page = Pages::get('section_b')) $menu->addPageDropdown($page, "Section B: Academic Freedom &amp; Tenure", true);
        if ($page = Pages::get('section_c')) $menu->addPageDropdown($page, "Section C: Faculty Rules and Benefits", true);
        if ($page = Pages::get('section_d')) $menu->addPageDropdown($page, "Section D: Teaching and Student-Related", true);
        if ($page = Pages::get('section_e')) $menu->addPageDropdown($page, "Section E: Research", true);
        if ($page = Pages::get('section_f')) $menu->addPageDropdown($page, "Section F: Branch Community Colleges", true);

        $menu->addURL(new URL('/pdf/'), 'Faculty Handbook PDFs');
        $menu->addURL(new URL('/abolished/'), 'Abolished policies');

        return $menu->__toString();
    },
    3600
);