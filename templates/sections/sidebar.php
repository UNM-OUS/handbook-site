<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\Search\SearchForm;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\MenuBar\MenuBar;
use DigraphCMS\URL\URL;

echo new SearchForm();

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

?>

<p style="break-inside: avoid;">
    <strong>Office of the University Secretary</strong><br>
    <?php echo Format::base64obfuscate('(505) 277-4664'); ?><br>
    <?php echo Format::base64obfuscate('<a href="mailto:handbook@unm.edu">handbook@unm.edu</a>'); ?>
</p>
<p style="break-inside: avoid;">
    MSC05 3340<br>
    Scholes Hall, 103<br>
    1 University of New Mexico<br>
    Albuquerque, NM 87131
</p>