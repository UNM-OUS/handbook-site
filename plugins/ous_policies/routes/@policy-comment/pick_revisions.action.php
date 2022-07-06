<?php

use DigraphCMS\Context;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\RevisionState;

/** @var CommentPage */
$page = Context::page();

?>
<h1>Pick revisions</h1>
<p>
    Revisions selected here will have their status immediately set to
    <em><?php echo (new RevisionState('pending'))->label(); ?></em>.
    Once the comment period begins, revisions will have their state set to
    <em><?php echo (new RevisionState('comment'))->label(); ?></em>.
    Once the revision period ends they will once again be set to
    <em><?php echo (new RevisionState('pending'))->label(); ?></em>,
    and they will need to be manually published or cancelled.
</p>

<div class="navigation-frame navigation-frame--stateless" id="revision-picker">
    <?php
    echo "<h2>Selected revisions</h2>";
    $revisions = $page->revisions();
    $table = new QueryTable(
        $revisions,
        function (PolicyRevision $revision) use ($page): array {
            return [
                $revision->policy()->name(),
                sprintf('<a href="%s" target="_blank">%s</a>', $revision->url(), $revision->title()),
                (new ToolbarLink('Remove', 'close', function () use ($page, $revision) {
                    $page->removeRevision($revision)
                        ->update();
                }, null, $revision->uuid() . '_remove'))
                    ->setData('target', 'revision-picker')
            ];
        }
    );
    echo $table;

    echo "<h2>All revisions</h2>";
    $recentRevisions = Revisions::select();
    foreach ($revisions as $revision) {
        $recentRevisions->where('uuid <> ?', [$revision->uuid()]);
    }
    $table = new QueryTable(
        $recentRevisions,
        function (PolicyRevision $revision) use ($page): array {
            return [
                $revision->policy()->name(),
                sprintf('<a href="%s" target="_blank">%s</a>', $revision->url(), $revision->title()),
                (new ToolbarLink('Add', 'add', function () use ($page, $revision) {
                    $page->addRevision($revision)
                        ->update();
                }, null, $revision->uuid() . '_add'))
                    ->setData('target', 'revision-picker')
            ];
        }
    );
    echo $table;
    ?>
</div>