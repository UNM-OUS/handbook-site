<h1>Manage approvals</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\Approvals\Approvals;
use DigraphCMS_Plugins\unmous\ous_policies\Approvals\RevisionApproval;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

$revisions = Revisions::select(Context::pageUUID())
    ->order('effective DESC');

$table = new QueryTable(
    $revisions,
    function (PolicyRevision $revision): array {
        $query = Approvals::select($revision->uuid());
        $query->order('approved ASC');
        return [
            sprintf(
                '%s<a href="%s">%s</a>',
                new ToolbarLink("Add approval", "add", null, new URL('_add_approval.html?revision=' . $revision->uuid())),
                $revision->url(),
                $revision->title()
            ),
            Format::date($revision->effective()),
            new QueryTable(
                $query,
                function (RevisionApproval $approval): array {
                    return [
                        sprintf(
                            '<strong>%s: %s%s</strong><div>%s</div>',
                            Format::date($approval->approved()),
                            $approval->approver(),
                            $approval->hidden() ? ' [HIDDEN]' : '',
                            $approval->notes()
                        ),
                        implode('', [
                            (new ToolbarLink('Edit', 'edit', null, new URL('_edit_approval.html?uuid=' . $approval->uuid())))
                                ->setAttribute('data-target', '_top'),
                            (new ToolbarLink('Delete', 'delete', function () use ($approval) {
                                $approval->delete();
                            }))
                                ->setAttribute('data-target', '_frame')
                        ]),
                        Format::date($approval->updated()) . ' ' .
                            $approval->updatedBy(),
                        Format::date($approval->created()) . ' ' .
                            $approval->createdBy(),
                    ];
                },
                [
                    new ColumnHeader('Approval'),
                    new ColumnHeader(''),
                    new ColumnHeader('Updated'),
                    new ColumnHeader('Created'),
                ]
            )
        ];
    },
    [
        new ColumnHeader('Revision'),
        new ColumnHeader('Effective'),
        new ColumnHeader('')
    ]
);
$table->paginator()->perPage(5);
echo $table;
