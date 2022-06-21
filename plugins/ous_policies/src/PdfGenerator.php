<?php

namespace DigraphCMS_Plugins\unmous\ous_policies;

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
    public static function generateSectionPDF(string $slug_or_uuid, string $title, $skip = []): string
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
            'data' => static::generateSectionPDFData($page, $title, $skip)
        ])->execute();
        return "Generated PDF of " . $page->name();
    }

    public static function generateSectionPDFData(AbstractPage $page, string $title, $skip = []): string
    {
        // take as much memory and time as needed
        ini_set('memory_limit', '1024M');
        set_time_limit(600);
        // prepare skip list
        $skip = array_filter(array_map(
            function ($slug_or_uuid) {
                if ($page = Pages::get($slug_or_uuid)) return $page->uuid();
                else return false;
            },
            $skip
        ));
        // prepare html and turn it into a pdf
        $html = static::generateSectionCoverPageHTML($page, $title, $skip);
        $html .= static::generateSectionHTML($page, $skip);
        $html = Templates::render('policy/pdf-section.php', ['body' => $html]);
        $options = new Options();
        $options->setIsPhpEnabled(true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, "UTF-8");
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    protected static function generateSectionCoverPageHTML(AbstractPage $page, string $title, $skip = []): string
    {
        $title = $title ?? $page->name();
        ob_start();
        echo '<div id="header">' . $title . ' - ' . Format::datetime(time(), false, true) . '</div>';
        echo '<div id="footer">page <span class="page-number"></span></div>';
        echo '<div class="pdf-section">';
        printf('<p><img src="data:image/image/jpg;base64,%s" style="width:7.5in;"/></p>', base64_encode(Media::get('/hero.jpg')->content()));
        echo "<h1>" . $title . "</h1>";
        echo "<p><small>This PDF was generated " . Format::datetime(time(), true, true) . "</small></p>";
        echo "<p><small>For the most recent copy visit <a href='https://handbook.unm.edu/pdf/'>handbook.unm.edu/pdf</a></small></p>";
        echo '<h2 style="page-break-before:always;">Table of contents</h2>';
        echo '<table class="table-of-contents">';
        // echo '<tr><th>Policy</th><th width="1%">Page</th></tr>';
        echo static::generateSectionTocHTML($page, $skip);
        echo '</table>';
        echo '</div>';
        echo '<script type="text/php">$GLOBALS["max_objects"] = count($pdf->get_cpdf()->objects);</script>';
        return ob_get_clean();
    }

    protected static function generateSectionTocHTML(AbstractPage $page, $skip = [], &$seen = []): string
    {
        // avoid cycles
        if (in_array($page->uuid(), $seen)) return '';
        $seen[] = $page->uuid();
        // start output buffering
        ob_start();
        // prepare output if this one isn't to be skipped
        if ($page instanceof PolicyPage && !in_array($page->uuid(), $skip)) {
            echo "<tr>";
            echo '<td><a href="#policy-' . $page->uuid() . '">' . $page->name() . '</a></td>';
            echo "<td>%%" . $page->uuid() . "%%</td>";
            echo "</tr>";
        }
        // recurse, even for skipped
        foreach ($page->children() as $child) {
            echo static::generateSectionTocHTML($child, $skip, $seen);
        }
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
        if ($page instanceof PolicyPage && !in_array($page->uuid(), $skip)) {
            echo "<div class='pdf-section' id='policy-" . $page->uuid() . "'>";
            printf(
                '<script type="text/php">$GLOBALS["toc"]["%s"] = $pdf->get_page_number();</script>',
                $page->uuid()
            );
            Context::begin();
            Context::page($page);
            echo $page->richContent('body');
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
