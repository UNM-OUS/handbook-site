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
</body>

</html>