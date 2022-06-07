<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\MenuBar\MenuBar;
use DigraphCMS\URL\URL;

$menu = new MenuBar;
$menu->addClass('menubar--vertical');
$menu->addClass('menubar--manual-toggle');
$menu->addClass('sidebar__menu');

if ($page = Pages::get('section_a')) $menu->addPageDropdown($page, null, true);
if ($page = Pages::get('section_b')) $menu->addPageDropdown($page, null, true);
if ($page = Pages::get('section_c')) $menu->addPageDropdown($page, null, true);
if ($page = Pages::get('section_d')) $menu->addPageDropdown($page, null, true);
if ($page = Pages::get('section_e')) $menu->addPageDropdown($page, null, true);
if ($page = Pages::get('section_f')) $menu->addPageDropdown($page, null, true);

$menu->addURL(new URL('/pdf/'), 'Faculty Handbook PDFs');

echo $menu;

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