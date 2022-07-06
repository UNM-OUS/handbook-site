<?php

use DigraphCMS\Context;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;

Context::response()->cacheTTL(600);
Context::response()->browserTTL(600);

/** @var CommentPage */
$page = Context::page();

echo $page->richContent('body');

$revisions = $page->revisions();
if (!$revisions) return;

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
    if ($revision->moved() && $revision->fullName() != $revision->policy()->name()) {
        echo '<br><small>Note: being moved/renamed to <strong>' . $revision->fullName() . '</strong></small>';
    }
    if (!in_array($revision->state(), ['pending', 'comment'])) {
        echo ' (' . $revision->state()->label() . ')';
    }
    echo "</li>";
}
echo "</ul>";
