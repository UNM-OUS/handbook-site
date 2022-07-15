<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Sidebar\Sidebar;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;
use DigraphCMS_Plugins\unmous\ous_policies\PolicyPage;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;

Context::response()->enableCache();

/** @var PolicyPage */
$page = Context::page();

$comment = [];
foreach ($page->futureRevisions() as $revision) {
    $comment = array_merge($comment, $revision->currentCommentPeriods());
}
$comment = array_unique($comment);
if ($comment) {
    Notifications::printConfirmation(sprintf(
        '<p>A review and comment period for proposed revisions to this policy is available:<br>%s</p>',
        implode('<br>', array_map(
            function (CommentPage $page) {
                return $page->url()->html();
            },
            $comment
        ))
    ));
}

echo $page->richContent('body');

Sidebar::addBottom(function () {
    if (Context::page()->revisions()->count() < 2) return '';
    /** @var PolicyRevision */
    $earliest = Context::page()->revisions()
        ->where('effective is not null')
        ->order('effective asc')
        ->limit(1)
        ->fetch();
    $out = '<div class="card card--info">';
    $out .= '<h1 style="margin-top:0;">Revision history</h1>';
    $out .= sprintf(
        '<p>The revision history of %s as far back as %s is available online through our self-service portal. Locating older revisions would require a manual search of historical records. For more information you may also visit %s.</p>',
        Context::page()->name(),
        Format::date($earliest->effective()),
        (new URL('/locating_old_versions/'))->html()
    );
    $out .= sprintf(
        '<a href="%s">%s</a>',
        Context::page()->url('_revision_history'),
        'Revision history of ' . Context::page()->name()
    );
    $out .= '</div>';
    return $out;
});
