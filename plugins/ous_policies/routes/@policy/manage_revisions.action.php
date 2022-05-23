<h1>Manage revisions</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\PolicyPage;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

/** @var PolicyPage */
$policy = Context::page();

if (Revisions::select(Context::pageUUID())->count() == 0) {
    printf(
        "<a href='%s' class='button'>Draft blank revision</a>",
        new URL('_draft_revision.html')
    );
    return;
}

$drafts = Revisions::select($policy->uuid())
    ->where('state = "draft"')
    ->order('updated DESC');
if ($drafts->count()) {
    echo "<h2>Unpublished</h2>";
    echo new QueryTable(
        $drafts,
        function (PolicyRevision $revision): array {
            return [
                sprintf('<a href="%s">%s</a>', new URL('_edit_revision.html?uuid=' . $revision->uuid()), $revision->title()),
                $revision->effective() ? Format::date($revision->effective()) : '-',
                $revision->type()->label(),
                Format::date($revision->updated()),
                $revision->updatedBy(),
                implode('', [
                    new ToolbarLink('Publish', 'publish', function () use ($revision) {
                        $revision->setState('published')->update();
                    }),
                    new ToolbarLink('Publish (pending)', 'pending', function () use ($revision) {
                        $revision->setState('pending')->update();
                    }),
                    new ToolbarLink('Publish (hidden)', 'hide', function () use ($revision) {
                        $revision->setState('hidden')->update();
                    }),
                ]),
            ];
        },
        [
            new ColumnHeader(''),
            new QueryColumnHeader('Effective', 'effective', $drafts),
            new ColumnHeader('Type'),
            new QueryColumnHeader('Modified', 'updated', $drafts),
            new ColumnHeader(''),
            new ColumnHeader(''),
        ]
    );
    echo "<h2>Published</h2>";
}

$revisions = Revisions::select($policy->uuid())
    ->where('state <> "draft"')
    ->order('(CASE WHEN effective IS NULL THEN 1 ELSE 2 END), effective DESC, created DESC');
echo new QueryTable(
    $revisions,
    function (PolicyRevision $revision): array {
        return [
            implode(PHP_EOL, [
                new ToolbarLink('Copy into draft', 'copy', null, new URL('_draft_revision.html?from=' . $revision->uuid())),
                sprintf('<a href="%s">%s</a>', new URL('_edit_revision.html?uuid=' . $revision->uuid()), $revision->title())
            ]),
            $revision->effective() ? Format::date($revision->effective()) : '<em>not set</em>',
            $revision->type()->label(),
            $revision->state()->label(),
            Format::date($revision->created()),
            $revision->createdBy(),
            Format::date($revision->updated()),
            $revision->updatedBy()
        ];
    },
    [
        new ColumnHeader(''),
        new QueryColumnHeader('Effective', 'effective', $revisions),
        new ColumnHeader('Type'),
        new ColumnHeader('State'),
        new QueryColumnHeader('Created', 'created', $revisions),
        new ColumnHeader(''),
        new QueryColumnHeader('Modified', 'updated', $revisions),
        new ColumnHeader('')
    ]
);
