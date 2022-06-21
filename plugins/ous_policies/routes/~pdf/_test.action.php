<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_policies\PdfGenerator;

Permissions::requireGroup('admins');

Context::response()->filename('test.pdf');
echo PdfGenerator::generateSectionPDFData(Pages::get('policies'), 'Test PDF');
return;
