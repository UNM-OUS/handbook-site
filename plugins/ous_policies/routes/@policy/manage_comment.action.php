<h1>Manage comment periods</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPeriods;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\RevisionComment;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

$revisions = Revisions::select(Context::pageUUID())
    ->order('CASE WHEN effective is null THEN 1 ELSE 2 END')
    ->order('effective DESC');

$table = new QueryTable(
    $revisions,
    function (PolicyRevision $revision): array {
        $query = CommentPeriods::select($revision->uuid());
        $query->order('start ASC');
        return [
            sprintf(
                '%s<a href="%s">%s</a>',
                new ToolbarLink("Add comment period", "add", null, new URL('_add_comment.html?revision=' . $revision->uuid())),
                $revision->url(),
                $revision->metaTitle()
            ),
            new QueryTable(
                $query,
                function (RevisionComment $comment): array {
                    return [
                        sprintf(
                            '<strong><a href="%s">%s - %s<br>%s</a></strong>',
                            $comment->url(),
                            Format::date($comment->start()),
                            Format::date($comment->end()),
                            $comment->name()
                        ),
                        implode('', [
                            (new ToolbarLink('Edit', 'edit', null, new URL('_edit_comment.html?uuid=' . $comment->uuid())))
                                ->setAttribute('data-target', '_top'),
                            (new ToolbarLink('Delete', 'delete', function () use ($comment) {
                                $comment->delete();
                            }))
                                ->setAttribute('data-target', '_frame')
                        ]),
                        Format::date($comment->updated()) . ' ' .
                            $comment->updatedBy(),
                        Format::date($comment->created()) . ' ' .
                            $comment->createdBy(),
                    ];
                },
                [
                    new ColumnHeader('Comment'),
                    new ColumnHeader(''),
                    new ColumnHeader('Updated'),
                    new ColumnHeader('Created'),
                ]
            )
        ];
    },
    [
        new ColumnHeader('Revision'),
        new ColumnHeader('')
    ]
);
$table->paginator()->perPage(5);
echo $table;
