<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\URL\URL;

$top = Breadcrumb::top();
$top->setName('Disambiguation page');
Breadcrumb::top($top);
Breadcrumb::parent(new URL('/'));

$requestUrl = Context::request()->originalUrl();
$staticUrl = Context::data('300_static');
$pages = Context::data('300_pages');

?>
<h1>Multiple options</h1>

<p>
    The requested URL can be resolved to more than one piece of content.
    To avoid breaking any links or causing external links to point at a completely different policy than intended, this disambiguation page has been generated to explain the situation.
</p>
<p>
    Please choose an option below from the list of pages and polcies that have existed at this URL.
</p>

<ul class='error-options-300'>
    <?php
    $urls = [];
    if ($staticUrl) {
        echo "<li>" . $staticUrl->html() . "</li>";
        $urls[] = $staticUrl;
    }
    foreach ($pages as $page) {
        if (!Router::pageRouteExists($page, $requestUrl->action())) {
            continue;
        }
        $urls[] = $url = $page->url($requestUrl->action(), $requestUrl->query());
        $url->normalize();
        echo "<li>" . $url->html() . "</li>";
    }
    /**
     * If only one URL was found, redirect straight to it. This is a rare
     * situation, and it's better to do this check here when there's already a
     * 300 error potential than to complicate/slow every single pageview doing
     * these kinds of checks there.
     */
    if (count($urls) == 1) {
        throw new RedirectException(reset($urls));
    }
    ?>
</ul>

<p>
    This may happen to policy links if a policy number is reused after an earlier policy with that number was abolished or renumbered.
</p>

<p>
    If you control a website that links to this URL, please update your site to point at one of the options above instead of this URL.
    The options above are less human-readable, but they are unambiguous and will continue to point at the specific policies they are assigned even if the policies are renamed or moved in the future.
</p>

<?php
Router::include('/~error/trace.php');
