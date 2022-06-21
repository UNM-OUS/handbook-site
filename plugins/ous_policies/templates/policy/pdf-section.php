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
        $GLOBALS['toc'] = [];
        $GLOBALS['max_objects'] = 0;
    </script>
    <?php echo Context::fields()['body']; ?>
    <script type="text/php">
        for ($i = 0; $i <= $GLOBALS['max_objects']; $i++) {
            if (!array_key_exists($i, $pdf->get_cpdf()->objects)) continue;
            $object = $pdf->get_cpdf()->objects[$i];
            if (!array_key_exists('c', $object)) continue;
            foreach ($GLOBALS['toc'] as $id => $page) {
                $object['c'] = str_replace( '%%'.$id.'%%' , $page , $object['c'] );
            }
            $pdf->get_cpdf()->objects[$i] = $object;
        }
    </script>
</body>

</html>