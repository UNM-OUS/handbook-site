<?php

use DigraphCMS\Context;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPeriods;

Context::response()->setSearchIndex(!Context::url()->query());

?>

<h1>Policy revisions under review</h1>

<p>
    The following faculty review and comment periods have been posted regarding proposed policy revisions.
    Please email your comments to <?php Format::base64obfuscate('<a href="mailto:handbook@unm.edu">handbook@unm.edu</a>'); ?>.
    All comments will be provided to the committees.
</p>

<p>
    The Office of the University Secretary will review the comments and determine the best approach to address concerns raised.
    This may include consultation with faculty members involved in development of the proposed policy or change.
    In consultation with the Chair of the Faculty Committee who developed the proposed policy or proposed changes, the Office of the University Secretary will prepare the recommended policy document for Faculty Senate or University faculty consideration.
</p>

<?php

$current = CommentPeriods::all();
if ($current->count()) {
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

echo "<h2>Past comment periods</h2>";

$past = CommentPeriods::past();
$table = new QueryTable(
    $past,
    function (CommentPage $page) {
        return [$page->url()->html()];
    },
    []
);
echo $table;
