<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS_Plugins\unmous\ous_policies\PdfGenerator;

Context::response()->filename('test.pdf');
echo PdfGenerator::generateSectionPDFData(Pages::get('policies'),'Test PDF');
return;