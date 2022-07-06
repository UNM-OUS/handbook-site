<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\PolicyPage;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;

Context::response()->enableCache();

/** @var PolicyPage */
$page = Context::page();

echo $page->richContent('body');

if ($page->revisions()->count() > 1) {
    /** @var PolicyRevision */
    $earliest = $page->revisions()
        ->where('effective is not null')
        ->order('effective asc')
        ->limit(1)
        ->fetch();
    echo '<div class="card card--info">';
    echo '<h2>Revision history</h2>';
    printf(
        '<p>The revision history of this policy as far back as %s is available online through our self-service portal. Locating older revisions would require a manual search of historical records. For more information you may also visit %s.</p>',
        Format::date($earliest->effective()),
        (new URL('/locating_old_versions/'))->html()
    );
    printf(
        '<a href="%s">%s</a>',
        $page->url('_revision_history'),
        'Revision history of ' . $page->name()
    );
    echo '</div>';
}
