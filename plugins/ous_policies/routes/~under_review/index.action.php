<?php

use DigraphCMS\Context;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPeriods;

Context::response()->setSearchIndex(!Context::url()->query());
Context::fields()['template-sidebar'] = true;

?>

<h1>Policy revisions under review</h1>

<p>
    The following faculty review and comment periods have been posted regarding proposed policy revisions.
    All comments will be provided to the relevant committees.
    Please email your comments to <?php echo Format::base64obfuscate('<a href="mailto:handbook@unm.edu">handbook@unm.edu</a>'); ?>
</p>

<p>
    The Office of the University Secretary will review the comments and determine the best approach to address concerns raised.
    This may include consultation with faculty members involved in development of the proposed policy or change.
    In consultation with the Chair of the Faculty Committee who developed the proposed policy or proposed changes, the Office of the University Secretary will prepare the recommended policy document for Faculty Senate or University faculty consideration.
</p>

<?php

$current = CommentPeriods::current();
if (!$current->count()) {
    Notifications::printNotice('No comment periods are currently open');
} else {
    $table = new QueryTable(
        $current,
        function (CommentPage $page) {
            return [$page->url()->html()];
        },
        []
    );
    echo "<h2>Current comment periods</h2>";
    echo $table;
}

$past = CommentPeriods::past();
if (!$past->count()) return;

echo "<h2>Past comment periods</h2>";

$table = new QueryTable(
    $past,
    function (CommentPage $page) {
        return [$page->url()->html()];
    },
    []
);
echo $table;
