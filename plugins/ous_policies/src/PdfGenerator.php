<?php

namespace DigraphCMS_Plugins\unmous\ous_policies;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\Media\Media;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Templates;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGenerator
{
    protected static $startTime;

    public static function generateSectionPDF(string $slug_or_uuid, string $title): string
    {
        $page = Pages::get($slug_or_uuid);
        if (!$page) throw new \Exception("Couldn't generate PDF from section, slug or UUID \"$slug_or_uuid\" not found");
        DB::query()->insertInto('generated_policy_pdf', [
            'uuid' => Digraph::uuid(),
            'page_uuid' => $page->uuid(),
            'date_year' => date('Y'),
            'date_month' => date('n'),
            'date_day' => date('j'),
            'filename' => $title . ' - ' . date('Y-m-d') . '.pdf',
            'created' => time(),
            'data' => gzencode(static::generateSectionPDFSource($page, $title), 9)
        ])->execute();
        return "Generated PDF of " . $page->name();
    }

    public static function generateSectionPDFSource(AbstractPage $page, string $title): string
    {
        // take as much memory and time as needed
        ini_set('memory_limit', '2048M');
        set_time_limit(120);
        static::$startTime = time();
        // prepare html and turn it into a pdf
        $html = static::generateSectionCoverPageHTML($page, $title);
        $html .= static::generateSectionHTML($page);
        $html = Templates::render('policy/pdf-section.php', ['body' => $html]);
        $options = new Options();
        $options->setIsPhpEnabled(true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, "UTF-8");
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    protected static function generateSectionCoverPageHTML(AbstractPage $page, string $title): string
    {
        $title = $title ?? $page->name();
        $out = '';
        $out .= '<div id="header">' . $title . ' - ' . Format::datetime(time(), false, true) . '</div>';
        $out .= '<div id="footer">page <span class="page-number"></span></div>';
        $out .= '<div class="pdf-section">';
        $out .= sprintf('<p><img src="data:image/image/jpg;base64,%s" style="width:7.5in;"/></p>', base64_encode(Media::get('/hero.jpg')->content()));
        $out .= "<h1>" . $title . "</h1>";
        $out .= "<p><small>This PDF was generated " . Format::datetime(time(), true, true) . "</small></p>";
        $out .= "<p><small>For the most recent copy visit <a href='https://handbook.unm.edu/pdf/'>handbook.unm.edu/pdf</a></small></p>";
        $out .= '<h2 style="page-break-before:always;">Table of contents</h2>';
        $out .= '<table class="table-of-contents">';
        $out .= static::generateSectionTocHTML($page);
        $out .= '</table>';
        $out .= '</div>';
        $out .= '<script type="text/php">$GLOBALS["max_objects"] = count($pdf->get_cpdf()->objects);</script>';
        return $out;
    }

    protected static function generateSectionTocHTML(AbstractPage $page): string
    {
        return Cache::get(
            'policy/pdf/toc/' . $page->uuid(),
            function () use ($page) {
                $out = '';
                // prepare output if this one isn't to be skipped
                if ($page instanceof PolicyPage) {
                    $out .= "<tr>";
                    $out .= '<td><a href="#policy-' . $page->uuid() . '">' . $page->name() . '</a></td>';
                    $out .= "<td>%%" . $page->uuid() . "%%</td>";
                    $out .= "</tr>";
                }
                // recurse, even for skipped
                foreach ($page->children() as $child) {
                    $out .= static::generateSectionTocHTML($child, $seen);
                }
                return $out;
            },
            12 * 3600
        );
    }

    protected static function generateSectionHTML(AbstractPage $page): string
    {
        if ((time() - static::$startTime) > 30) throw new PdfGenerationTimeout("Generating PDF took too long");
        return Cache::get(
            'policy/pdf/section/' . $page->uuid(),
            function () use ($page) {
                $out = '';
                // prepare output if this one isn't to be skipped
                if ($page instanceof PolicyPage) {
                    $out .= "<div class='pdf-section' id='policy-" . $page->uuid() . "'>";
                    $out .= sprintf(
                        '<script type="text/php">$GLOBALS["toc"]["%s"] = $pdf->get_page_number();</script>',
                        $page->uuid()
                    );
                    $out .= Cache::get(
                        'policy/pdf/body/' . $page->uuid(),
                        function () use ($page) {
                            Context::begin();
                            Context::page($page);
                            $out = $page->richContent('body');
                            Context::end();
                            return $out;
                        },
                        12 * 3600
                    );
                    $out .= "</div>";
                }
                // recurse, even for skipped
                foreach ($page->children() as $child) {
                    $out .= static::generateSectionHTML($child, $seen);
                }
                return $out;
            },
            12 * 3600
        );
    }
}
