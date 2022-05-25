<h1>Policy revision history</h1>
<?php

use DigraphCMS\UI\DataTables\CellWriters\DateCell;
use DigraphCMS\UI\DataTables\CellWriters\LinkCell;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

$revisions = Revisions::select()
    ->where('effective is not null')
    ->where('effective <= ?', [date('Y-m-d')])
    ->order('effective DESC');

$table = new QueryTable(
    $revisions,
    function (PolicyRevision $revision): array {
        return [
            sprintf(
                '<a href="%s">%s</a>',
                $revision->policy()->url(),
                $revision->fullName()
            ),
            sprintf(
                '<a href="%s">%s</a>',
                $revision->url(),
                $revision->title()
            ),
            $revision->effective() ? Format::date($revision->effective()) : ''
        ];
    },
    [
        new QueryColumnHeader('Policy', 'num', $revisions),
        new ColumnHeader('Revision information'),
        new QueryColumnHeader('Effective', 'effective', $revisions)
    ]
);

$table->enableDownload(
    'Faculty Handbook revision history',
    function (PolicyRevision $revision): array {
        return [
            new LinkCell($revision->fullName(), $revision->policy()->url()),
            new LinkCell($revision->title(), $revision->url()),
            new DateCell($revision->effective()),
            $revision->type()->__toString()
        ];
    },
    [
        'Policy',
        'Revision information',
        'Effective',
        'Revision type',
    ]
);

echo $table;
