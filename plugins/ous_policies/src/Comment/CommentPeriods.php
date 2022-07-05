<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Comment;

use DigraphCMS\Content\Pages;
use DigraphCMS\Content\PageSelect;

class CommentPeriods
{
    public static function all(): PageSelect
    {
        return Pages::select()
            ->where('class = ?', 'policy-comment')
            ->order('${data.first_day} desc');
    }

    public static function current(): PageSelect
    {
        return static::all()
            ->where('${data.first_day} <= ?', [date('Y-m-d')])
            ->where('${data.last_day} >= ?', [date('Y-m-d')])
            ->order(null)
            ->order('${data.last_day} desc');
    }

    public static function upcoming(): PageSelect
    {
        return static::all()
            ->where('${data.first_day} > ?', [date('Y-m-d')])
            ->order(null)
            ->order('${data.first_day} asc');
    }

    public static function past(): PageSelect
    {
        return static::all()
            ->where('${data.last_day} < ?', [date('Y-m-d')])
            ->order(null)
            ->order('${data.last_day} desc');
    }
}
