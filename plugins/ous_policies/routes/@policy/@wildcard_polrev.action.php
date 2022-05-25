<?php

use Caxy\HtmlDiff\HtmlDiff;
use DigraphCMS\Context;
use DigraphCMS\HTML\Icon;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Toolbars\ToolbarSpacer;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

// verify that revision exists
$uuid = Context::url()->action();
$revision = Revisions::get($uuid, Context::pageUUID());
if (!$revision) throw new HttpError(404);

// require edit permissions if revision is a draft or hidden
if ($revision->state() == 'draft') Permissions::requireMetaGroup('policies__edit');
if ($revision->state() == 'hidden') Permissions::requireMetaGroup('policies__edit');

// fix up breadcrumb
Breadcrumb::top($revision->url());
Breadcrumb::parent(new URL('_revision_history.html'));
ActionMenu::addContextAction(new URL("_edit_revision.html?uuid=$uuid"));
ActionMenu::addContextAction(new URL("_delete_revision.html?uuid=$uuid"));

printf('<h1><a href="%s">Revision history</a></h1>', new URL('revision_history.html'));

$current = Context::page()->currentRevision();
if (Context::page()->revisions()->count() > 1) {
    echo "<div class='toolbar'>";
    if ($prev = $revision->previousRevision()) echo new Icon('previous') . '  ' . $prev->url()->html();
    echo new ToolbarSpacer;
    if ($next = $revision->nextRevision()) echo $next->url()->html() . ' ' . new Icon('next');
    echo "</div>";
}
echo "<div class='card card--light'>";

echo "<h2>" . $revision->metaTitle() . "</h2>";
echo "<p>Revision type: " . $revision->type()->label();
echo "<br>Revision state: " . $revision->state()->label();
if (!$revision->effective()) {
    echo " (No effective date scheduled)";
} elseif ($revision->effective() > new DateTime) {
    echo ' (Pending effective date ' . Format::date($revision->effective()) . ')';
} elseif ($current) {
    if ($current->uuid() != $revision->uuid()) {
        if ($current->effective() > $revision->effective()) {
            echo ' (Superseded)';
        }
    } else {
        echo ' (Current revision)';
    }
}
echo "</p>";
echo $revision->notes();

echo "</div>";

// prepare content
$previous = $revision->previousRevision();
if (!$previous) {
    $body = $revision->body();
} else {
    $htmlDiff = new HtmlDiff($previous->body(), $revision->body());
    $body = $htmlDiff->build();
}

// display body
echo $body;
