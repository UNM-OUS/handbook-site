<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Media\Media;
use DigraphCMS\UI\MenuBar\MenuBar;
use DigraphCMS\URL\URL;

$menu = new MenuBar;
$menu->addClass('menubar--vertical');
$menu->addClass('menubar--manual-toggle');
$menu->addClass('sidebar__menu');

sidebarSectionTOC('section_a', $menu);
sidebarSectionTOC('section_b', $menu);
sidebarSectionTOC('section_c', $menu);
sidebarSectionTOC('section_d', $menu);
sidebarSectionTOC('section_e', $menu);
sidebarSectionTOC('section_f', $menu);

$menu->addURL(new URL('/pdf/'), 'Faculty Handbook PDFs');

echo $menu;

?>

<p style="break-inside: avoid;">
    Office of the University Secretary<br>
    MSC05 3340<br>
    Scholes Hall, 103<br>
    1 University of New Mexico<br>
    Albuquerque, NM 87131<br>
    <br>
    Phone: (505) 277-4664<br>
    handbook@unm.edu
</p>

<?php

function sidebarSectionTOC(string $slug, MenuBar $menu)
{
    $section = Pages::get($slug);
    if (!$section) return;
    $menuItem = $menu->addPage($section);
    // $subMenu = new MenuBar;
    // $menuItem->addChild($subMenu);
    // $subMenu->addPage(Pages::get('a10'), 'A10');
    // $subMenu->addPage(Pages::get('b10'), 'B20');
}
