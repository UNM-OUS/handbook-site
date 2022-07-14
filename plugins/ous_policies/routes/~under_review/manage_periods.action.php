<h1>Manage comment periods</h1>
<?php

use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPeriods;

$periods = CommentPeriods::all()
    ->order(null)
    ->order('${data.last_day} DESC');
$table = new PaginatedTable(
    $periods,
    function (CommentPage $page): array {
        return [
            $page->url()->html(),
            Format::date($page->firstDay(), true),
            Format::date($page->lastDay(), true)
        ];
    },
    [
        "Comment period",
        new ColumnSortingHeader("First day", '${data.first_day}', $periods),
        new ColumnSortingHeader("Last day", '${data.last_day}', $periods)
    ]
);

echo $table;
