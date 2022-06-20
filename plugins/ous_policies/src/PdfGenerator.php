<?php

namespace DigraphCMS_Plugins\unmous\ous_policies;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Templates;
use Dompdf\Dompdf;

class PdfGenerator
{
    public static function generateSectionPDF(string $slug_or_uuid, string $title, $skip = []): string
    {
        $page = Pages::get($slug_or_uuid);
        if (!$page) throw new \Exception("Couldn't generate PDF from section, slug or UUID \"$slug_or_uuid\" not found");
        DB::query()->insertInto('generated_policy_pdf',[
            'uuid' => Digraph::uuid(),
            'page_uuid' => $page->uuid(),
            'date_year' => date('Y'),
            'date_month' => date('n'),
            'date_day' => date('j'),
            'filename' => $title . ' - ' . date('Y-m-d') . '.pdf',
            'created' => time(),
            'data' => static::generateSectionPDFData($page, $title, $skip)
        ])->execute();
        return "Generated PDF of " . $page->name();
    }

    public static function generateSectionPDFData(AbstractPage $page, string $title, $skip = []): string
    {
        $skip = array_filter(array_map(
            function ($slug_or_uuid) {
                if ($page = Pages::get($slug_or_uuid)) return $page->uuid();
                else return false;
            },
            $skip
        ));
        $html = static::generateSectionCoverPageHTML($page, $title);
        $html .= static::generateSectionHTML($page, $skip);
        $html = Templates::render('policy/pdf-section.php', ['body' => $html]);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, "UTF-8");
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    protected static function generateSectionCoverPageHTML(AbstractPage $page, string $title): string
    {
        ob_start();
        echo "<div class='pdf-section pdf-section--cover-page'>";
        echo "<h1>" . ($title ?? $page->name()) . "</h1>";
        echo "<p>Generated " . Format::datetime(time(), false, true) . "</p>";
        echo "</div>";
        return ob_get_clean();
    }

    protected static function generateSectionHTML(AbstractPage $page, $skip = [], &$seen = []): string
    {
        // avoid cycles
        if (in_array($page->uuid(), $seen)) return '';
        $seen[] = $page->uuid();
        // start output buffering
        ob_start();
        // prepare output if this one isn't to be skipped
        if (!in_array($page->uuid(), $skip)) {
            echo "<div class='pdf-section'>";
            Context::begin();
            Context::page($page);
            if (method_exists($page, 'pdfBody')) echo $page->pdfBody();
            else echo $page->richContent('body');
            Context::end();
            echo "</div>";
        }
        // recurse, even for skipped
        foreach ($page->children() as $child) {
            echo static::generateSectionHTML($child, $skip, $seen);
        }
        return ob_get_clean();
    }
}
