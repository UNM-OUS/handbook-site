<h1>PDF download of Faculty Handbook</h1>
<p>
    Use these links to download PDF versions of either the entire Handbook or of individual sections.
    The following PDFs are rebuilt once a day to ensure they always match the content of the website.
    PDFs downloaded here will indicate the date they were built.
</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;

Context::response()->enableCache();
Context::response()->setSearchIndex(!Context::url()->query());
Context::fields()['template-sidebar'] = true;

// recent PDFs
$query = DB::query()->from('generated_policy_pdf')
    ->where('date_year = ?', [date('Y')])
    ->where('date_month = ?', [date('n')])
    ->where('date_day = ?', [date('j')])
    ->order('filename asc');

$table = new QueryTable(
    $query,
    function (array $row): array {
        $file = new DeferredFile($row['filename'], function (DeferredFile $file) use ($row) {
            file_put_contents($file->path(), gzdecode($row['data']));
        }, $row['uuid']);
        return [
            sprintf('<a href="%s">%s</a>', $file->url(), $file->filename())
        ];
    },
    []
);
if (!$query->count()) {
    Notifications::printWarning('Today\'s PDFs have not been generated yet. Please check back later.');
} elseif ($query->count() < 7) {
    Notifications::printNotice('Today\'s PDFs have not all been generated yet. Please check back later.');
    echo $table;
} else {
    echo $table;
}

// old PDFs

$query = DB::query()->from('generated_policy_pdf')
    ->where('date_day = 1')
    ->order('date_year desc, date_month desc, date_day desc, filename asc');
if (!$query->count()) return;

$table = new QueryTable(
    $query,
    function (array $row): array {
        $file = new DeferredFile($row['filename'], function (DeferredFile $file) use ($row) {
            file_put_contents($file->path(), gzdecode($row['data']));
        }, $row['uuid']);
        return [
            Format::date($row['created']),
            sprintf('<a href="%s">%s</a>', $file->url(), $file->filename())
        ];
    },
    []
);
echo "<h2>Archived copies</h2>";
echo '<p>PDFs are periodically automatically archived, and all archived copies can be browsed here.</p>';
echo $table;
