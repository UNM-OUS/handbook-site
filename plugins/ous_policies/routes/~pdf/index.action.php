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

Context::fields()['template-sidebar'] = true;

// recent PDFs

$query = DB::query()->from('generated_policy_pdf')
    ->where('created >= ?', [strtotime('yesterday')]);
if (!$query->count()) return;

$table = new QueryTable(
    $query,
    function (array $row): array {
        $file = new DeferredFile($row['filename'], function (DeferredFile $file) use ($row) {
            file_put_contents($file->path(), $row['data']);
        }, $row['uuid']);
        return [
            sprintf('<a href="%s">%s</a>', $file->url(), $file->filename())
        ];
    },
    []
);
echo $table;

// old PDFs

$query = DB::query()->from('generated_policy_pdf')
    ->where('date_day = 1');
if (!$query->count()) return;

$table = new QueryTable(
    $query,
    function (array $row): array {
        $file = new DeferredFile($row['filename'], function (DeferredFile $file) use ($row) {
            file_put_contents($file->path(), $row['data']);
        }, $row['uuid']);
        return [
            Format::date($row['created']),
            sprintf('<a href="%s">%s</a>', $file->url(), $file->filename())
        ];
    },
    []
);
echo "<h2>Archived copies</h2>";
echo '<p>A copy of each PDF from the first of each month is preserved indefinitely, for archival purposes.</p>';
echo $table;
