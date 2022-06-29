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
            ->order('${data.comment_start} desc');
    }

    public static function current(): PageSelect
    {
        return static::all()
            ->where('${data.comment_start} <= ?', [date('Y-m-d')])
            ->where('${data.comment_end} >= ?', [date('Y-m-d')])
            ->order(null)
            ->order('${data.comment_end} desc');
    }

    public static function upcoming(): PageSelect
    {
        return static::all()
            ->where('${data.comment_start} > ?', [date('Y-m-d')])
            ->order(null)
            ->order('${data.comment_start} asc');
    }

    public static function past(): PageSelect
    {
        return static::all()
            ->where('${data.comment_end} < ?', [date('Y-m-d')])
            ->order(null)
            ->order('${data.comment_end} desc');
    }
}
