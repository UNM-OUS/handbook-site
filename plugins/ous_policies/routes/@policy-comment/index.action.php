<?php

use DigraphCMS\Context;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;

/** @var CommentPage */
$page = Context::page();

echo $page->richContent('body');

$revisions = $page->revisions();
if (!$revisions) return;

echo "<h2>Proposed chagnes</h2>";
echo "<ul>";
foreach ($revisions as $revision) {
    echo "<li>";
    echo $revision->url()->html();
    if (!in_array($revision->state(), ['pending', 'comment'])) {
        echo ' (' . $revision->state()->label() . ')';
    }
    echo "</li>";
}
echo "</ul>";
