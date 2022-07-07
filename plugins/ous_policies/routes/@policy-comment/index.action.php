<?php

use DigraphCMS\Context;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;

Context::response()->cacheTTL(600);
Context::response()->browserTTL(600);

/** @var CommentPage */
$page = Context::page();

echo $page->richContent('body');

$revisions = $page->revisions();
if (!$revisions->count()) return;

echo "<h2>Proposed changes</h2>";
echo "<ul>";
foreach ($revisions as $revision) {
    echo "<li>";
    echo '<strong>' . $revision->policy()->name() . '</strong>: ';
    echo sprintf(
        '<a href="%s">%s</a>',
        $revision->url(),
        $revision->title()
    );
    if (!in_array($revision->state(), ['pending', 'comment'])) {
        echo ' (' . $revision->state()->label() . ')';
    }
    $previous = $revision->previousRevision();
    if ($revision->moved() && $previous && $revision->fullName() != $previous->fullName()) {
        echo '<br><small>Note: Policy was formerly named <strong>' . $previous->fullName() . '</strong></small>';
    }
    if ($revision->fullName() != $revision->policy()->name()) {
        echo '<br><small>Note: this policy is now named <strong>' . $previous->fullName() . '</strong></small>';
    }
    echo "</li>";
}
echo "</ul>";
