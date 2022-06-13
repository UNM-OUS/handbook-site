<?php

namespace DigraphCMS_Plugins\byjoby\glossary;

use DigraphCMS\Content\Page;

class GlossaryPage extends Page
{
    public function routeClasses(): array
    {
        return ['glossary', 'page', '_any'];
    }
}
