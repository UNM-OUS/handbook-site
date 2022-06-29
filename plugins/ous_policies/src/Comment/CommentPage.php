<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Comment;

use DigraphCMS\Content\Page;

class CommentPage extends Page
{
    public function routeClasses(): array
    {
        return ['policy-comment', 'page', '_any'];
    }
}
