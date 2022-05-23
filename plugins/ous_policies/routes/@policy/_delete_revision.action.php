<h1>Delete revision</h1>
<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

$revision = Revisions::get(Context::arg('uuid'), Context::pageUUID());
if (!$revision) throw new HttpError(404);

$uuid = $revision->uuid();
Breadcrumb::parent($revision->url());
ActionMenu::addContextAction(new URL("_edit_revision.html?uuid=$uuid"));
ActionMenu::addContextAction(new URL("_delete_revision.html?uuid=$uuid"));

$problems = [];

// get all rich media to run prechecks with
$media = RichMedia::select()
    ->where('parent = ? OR parent = ?', [$uuid, $uuid . '_notes'])
    ->fetchAll();
if ($media) {
    $mediaUUIDs = array_map(
        function (AbstractRichMedia $media) {
            return $media->uuid();
        },
        $media
    );

    // check other revisions for references to this rich media
    $query = Revisions::select();
    foreach ($mediaUUIDs as $uuid) {
        $query->whereOr('data like ?', ["%$uuid%"]);
    }
    foreach ($query as $r) {
        if ($r->uuid() != $revision->uuid()) {
            $problems[] = sprintf('%s revision %s may reference rich media that will be deleted', Pages::get($r->pageUUID())->url()->html(), $r->url()->html());
        }
    }

    // check pages for references to this rich media
    $query = Pages::select();
    foreach ($mediaUUIDs as $uuid) {
        $query->whereOr('data like ?', ["%$uuid%"]);
    }
    foreach ($query as $p) {
        $problems[] = sprintf('%s may reference rich media that would be deleted', $p->url()->html());
    }
}

// list media to be deleted
if ($media) {
    echo "<h2>Rich media</h2>";
    echo "<p>Deleting this revision would delete the following rich media:</p>";
    echo "<ul>";
    echo implode(PHP_EOL, array_map(
        function (AbstractRichMedia $media) {
            return sprintf('<li>%s: %s</li>', $media->className(), $media->name());
        },
        $media
    ));
    echo "</ul>";
}

// display problems
if ($problems) {
    echo "<h2>Warning</h2>";
    foreach ($problems as $p) {
        Notifications::printWarning($p);
    }
}

// display confirmation step
echo "<h2>Confirmation</h2>";
if ($problems) {
    echo "<p>Due to potential data integrity problems, this revision cannot be entirely deleted. You can, however, mark it as hidden so that it no longer appears to the public. Links to or embeds of its media elsewhere will continue to function normally.</p>";
    echo new SingleButton('Hide this revision', function () use ($revision) {
        $revision->setState('hidden')->update();
        Notifications::flashConfirmation('Revision hidden');
        throw new RedirectException($revision->url());
    });
} else {
    echo new SingleButton('Delete revision &mdash; This action cannot be undone', function () use ($revision) {
        $revision->delete();
        Notifications::flashConfirmation('Revision deleted');
        throw new RedirectException(new URL('manage_revisions.html'));
    });
}
