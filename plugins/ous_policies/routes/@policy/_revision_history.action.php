<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_policies\Approvals\Approvals;
use DigraphCMS_Plugins\unmous\ous_policies\Approvals\RevisionApproval;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

Context::response()->enableCache();

printf('<h1>Revision history of %s</h1>', Context::page()->name());

$query = Revisions::select(Context::pageUUID())
    ->publicView()
    ->where('effective IS NOT NULL')
    ->order('effective DESC, created DESC');

echo new PaginatedTable(
    $query,
    function (PolicyRevision $revision): array {
        return [
            sprintf('<a href="%s">%s</a>', $revision->url(), $revision->title()),
            Format::date($revision->effective()),
            implode(PHP_EOL, array_map(
                function (RevisionApproval $approval): string {
                    return sprintf(
                        '<div>%s - %s</div>',
                        Format::date($approval->approved(), true, true),
                        $approval->approver()
                    );
                },
                Approvals::select($revision->uuid())->noHidden()->fetchAll()
            )),
            $revision->state()->label()
        ];
    },
    [
        new ColumnHeader('Revision'),
        new ColumnSortingHeader('Date', 'effective', $query),
        new ColumnHeader('Approved by'),
        new ColumnHeader('Status')
    ]
);
