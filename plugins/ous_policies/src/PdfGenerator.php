<?php

namespace DigraphCMS_Plugins\unmous\ous_policies;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\Pages;
use Dompdf\Dompdf;

class PdfGenerator
{
    public static function generateSectionPDF(string $slug_or_uuid, string $title): string
    {
        $page = Pages::get($slug_or_uuid);
        if (!$page) throw new \Exception("Couldn't generate PDF from section, slug or UUID \"$slug_or_uuid\" not found");
        $page_uuid = $page->uuid();
        $year = date('Y');
        $month = date('n');
        $day = date('j');
        $filename = $title . ' - ' . date('Y-m-d') . '.pdf';
        $data = static::generateSectionPDFData($page, $title);
        return "Generated PDF of " . $page->name();
    }

    protected static function generateSectionPDFData(AbstractPage $page, string $title): string
    {
        $html = static::generateSectionCoverPageHTML($page, $title);
        $html .= static::generateSectionHTML($page);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, "UTF-8");
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}