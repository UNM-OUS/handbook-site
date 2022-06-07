<?php
/*
This is the default template for pages that contain a full page of content, and
are not some sort of error or special case.
*/

use DigraphCMS\Context;
use DigraphCMS\Cron\Cron;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\UI\Theme;
use DigraphCMS\UI\UserMenu;

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
    <?php echo Theme::head(); ?>
</head>

<body class='template-default no-js <?php echo implode(' ', Theme::bodyClasses()); ?>'>
    <section id="skip-to-content">
        <a href="#content">Skip to content</a>
    </section>
    <?php
    echo Templates::render('unm/loboalerts.php');
    echo Templates::render('unm/top-nav.php');
    echo new UserMenu;
    echo Templates::render('sections/header.php');
    // decide whether to display sidebar
    $displaySidebar = Context::fields()['template-sidebar'] ?? false;
    if (!$displaySidebar) {
        $displaySidebar = Context::url()->page() && (Context::url()->action() == 'index');
    }
    // open sidebar
    if ($displaySidebar) {
        echo '<div id="content-wrapper">';
    }
    ?>
    <div id="content">
        <?php
        Breadcrumb::print();
        echo new ActionMenu;
        echo '<div id="main-content-wrapper">';
        Notifications::printSection();
        echo '<div id="main-content">';
        echo Context::response()->content();
        echo '</div>';
        echo '</div>';
        ?>
    </div>
    <?php
    // close sidebar
    if ($displaySidebar) {
        echo '<div id="content-sidebar">';
        echo Templates::render('sections/sidebar.php');
        echo '</div></div>';
    }
    echo Templates::render('sections/footer.php');
    echo Cron::renderPoorMansCron();
    ?>
</body>

</html>