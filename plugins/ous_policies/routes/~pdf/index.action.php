<h1>PDF download of Faculty Handbook</h1>
<p>
    Use these links to download PDF versions of either the entire Handbook or of individual sections.
    The following PDFs are rebuilt once a day to ensure they always match the content of the website.
    PDFs downloaded here will indicate the date they were built.
</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\Media\DeferredFile;

Context::fields()['template-sidebar'] = true;

$pdf = new DeferredFile(
    'test.pdf',
    function (DeferredFile $file) {
    },
    [
        'handbook pdf',
        time()
    ]
);

var_dump($pdf->url(), $pdf);
