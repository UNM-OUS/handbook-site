<?php

use DigraphCMS\Context;
use DigraphCMS\Media\Media;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo Context::fields()['page.name'] ?? 'Untitled'; ?>
        :: <?php echo Context::fields()['site.name']; ?>
    </title>
    <style>
        <?php echo Media::get('/styles_pdf/*.css')->content(); ?>
    </style>
</head>

<body>
    <script type="text/php">
        $GLOBALS['table-of-contents'] = [];
        $GLOBALS['pdf'] = $pdf->open_object();
    </script>
    <?php echo Context::fields()['body']; ?>
    <script type="text/php">
        foreach ($GLOBALS['table-of-contents'] as $id => $page) {
            $pdf->get_cpdf()->objects[$GLOBALS['pdf']]['c'] = str_replace( '%%'.$id.'%%' , $page , $pdf->get_cpdf()->objects[$GLOBALS['pdf']]['c'] );
        }
        $pdf->page_script('
            if ($PAGE_NUM==1 ) {
                $pdf->add_object($GLOBALS["pdf"],"add");
                $pdf->stop_object($GLOBALS["pdf"]);
            } 
        ');
    </script>
</body>

</html>