<?php

namespace DigraphCMS_Plugins\unmous\ous_policies;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;
use PDOException;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class Policies extends AbstractPlugin
{
    public function onCron_daily()
    {
        /* Clean up old generated PDFs, only preserves yesterday, today, and the 1st of each month */
        new DeferredJob(function () {
            $cutoff = strtotime('yesterday');
            $count = DB::query()
                ->delete('generated_policy_pdf')
                ->where('date_day <> 1')
                ->where('created < ?', [$cutoff])
                ->execute();
            return "Cleaned up $count old generated policy PDFs";
        });
    }

    public function onSearchHighlightSection(string $query)
    {
        $policies = [];
        preg_replace_callback(
            '/\b[a-f][\.\s\-_]?[0-9]{1,3}([\.\s\-_][0-9]{1,3})*\b/i',
            function ($m) use (&$policies) {
                $number = preg_replace('/[\.\s\-_]/', '.', $m[0]);
                $number = preg_replace('/([a-f])[\.]?/', '$1', $number);
                $revision = Revisions::select()
                    ->publicView()
                    ->where('`num` LIKE ?', [$number])
                    ->fetch();
                if (!$revision) return null;
                $policy = $revision->policy();
                $policies[$policy->uuid()] = $policy;
            },
            $query
        );
        if (!$policies) return;
        echo "<div class='search-results__highlighted-policies'>";
        if (count($policies) == 1) {
            echo "<strong>Looking for policy " . reset($policies)->url()->html() . "?</strong>";
        } else {
            echo '<strong>Looking for one of these policies?</strong>';
            foreach ($policies as $policy) {
                echo "<div>";
                echo $policy->url()->html();
                echo "</div>";
            }
        }
        echo "</div>";
    }

    /**
     * Check hourly and try to create generated PDFs.
     * 
     * They check if they've already been generated for the day, so this should
     * make them be generated once per day, pretty soon after midnight.
     *
     * @return void
     */
    public function onCron_hourly()
    {
        $today = DB::query()->from('generated_policy_pdf')
            ->where(
                'date_year = ? AND date_month = ? AND date_day = ?',
                [date('Y'), date('n'), date('j')]
            );
        if (!$today->count()) {
            new DeferredJob(function (DeferredJob $job) {
                $today = DB::query()->from('generated_policy_pdf')
                    ->where(
                        'date_year = ? AND date_month = ? AND date_day = ?',
                        [date('Y'), date('n'), date('j')]
                    );
                $pages = [
                    'section_f' => 'UNM FHB - Section F',
                    'section_e' => 'UNM FHB - Section E',
                    'section_d' => 'UNM FHB - Section D',
                    'section_c' => 'UNM FHB - Section C',
                    'section_b' => 'UNM FHB - Section B',
                    'section_a' => 'UNM FHB - Section A',
                    'policies' => 'UNM Faculty Handbook',
                ];
                $spawned = 0;
                foreach ($pages as $slug => $title) {
                    if ($page = Pages::get($slug)) {
                        $check = clone ($today);
                        $check->where('page_uuid = ?', [$page->uuid()]);
                        if (!$check->count()) {
                            $spawned++;
                            $job->spawn(function (DeferredJob $job) use ($slug, $title) {
                                try {
                                    return PdfGenerator::generateSectionPDF($slug, $title);
                                } catch (PdfGenerationTimeout $t) {
                                    $job->spawnClone();
                                    return "PDF generation timed out, cloning job to try again";
                                } catch (PDOException $p) {
                                    $job->spawnClone();
                                    return "PDF generation encountered DB error, cloning job to try again";
                                } catch (\Throwable $th) {
                                    throw $th;
                                }
                            });
                        }
                    }
                }
                return "Spawned $spawned jobs to generate section PDFs";
            });
        } else {
            return "All PDFs already generated today";
        }
    }

    public function getAllPolicies(string $parentUUID = null): array
    {
        if (!$parentUUID) {
            $policies = Pages::select()
                ->where('type = ?', ['policy']);
        } else {
            $policies = [];
        }
        return static::sortPages($policies);
    }

    public function onShortCode_indent(ShortcodeInterface $s): ?string
    {
        return (new DIV)
            ->addClass('policy-indent')
            ->setAttribute('markdown', '1')
            ->addChild($s->getContent());
    }

    public function onShortCode_policytoc(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode() ?? Context::pageUUID() ?? '');
        if (!$page) return null;
        $toc = new PolicyTableOfContents($page, $s->getParameter('prefix', ''));
        return $toc->__toString();
    }

    public function onShortCode_policyinfo(ShortcodeInterface $s): ?string
    {
        return (new DIV)
            ->addClass('card card--policyinfo')
            ->setAttribute('markdown', '1')
            ->addChild($s->getContent());
    }

    public function onShortCode_fhb(ShortcodeInterface $s): ?string
    {
        $revision = Revisions::select()
            ->publicView()
            ->where('`num` LIKE ?', [$s->getBbCode()])
            ->fetch();
        if (!$revision) return null;
        return (new A($revision->policy()->url()))
            ->addChild($s->getContent() ?? "FH " . strtoupper($s->getBbCode()))
            ->setAttribute('title', $revision->policy()->name());
        return null;
    }

    public function onShortCode_rpm(ShortcodeInterface $s): ?string
    {
        $url = null;
        $title = 'RPM Policy';
        $name = 'RPM Policy';
        if (preg_match('/([0-9]+)(\.([0-9]+))?/', $s->getBbCode(), $matches)) {
            $section = intval($matches[1]);
            $number = @$matches[3] ? intval($matches[3]) : null;
            if ($number) {
                $url = sprintf(
                    'https://policy.unm.edu/regents-policies/section-%s/%s-%s.html',
                    $section,
                    $section,
                    $number
                );
                $title = "RPM Section $section.$number";
                $name = "RPM $section.$number";
            } else {
                $url = sprintf(
                    'https://policy.unm.edu/regents-policies/section-%s/index.html',
                    $section
                );
                $title = "RPM Section $section";
                $name = "RPM $section";
            }
        } elseif (!$s->getBbCode() || $s->getBbCode() == 'preface') {
            $url = 'https://policy.unm.edu/regents-policies/index.html';
            $title = "RPM Preface";
            $name = "RPM Preface";
        } elseif ($s->getBbCode() == 'toc') {
            $url = 'https://policy.unm.edu/regents-policies/table-of-contents.html';
            $title = "RPM Table of Contents";
            $name = "RPM Table of Contents";
        } elseif ($s->getBbCode() == 'foreword') {
            $url = 'https://policy.unm.edu/regents-policies/foreword.html';
            $title = "RPM Foreword";
            $name = "RPM Foreword";
        }
        return (new A($url))
            ->addChild($s->getContent() ?? $name)
            ->setAttribute('title', $title);
        return null;
    }

    public function onShortCode_uap(ShortcodeInterface $s): ?string
    {
        $url = null;
        $title = 'UAP Policy';
        $name = 'UAP Policy';
        if (!$s->getBbCode() || $s->getBbCode() == 'preface') {
            $url = 'https://policy.unm.edu/university-policies/index.html';
            $title = "UAP Preface";
            $name = "UAP Preface";
        } elseif ($s->getBbCode() == 'toc') {
            $url = 'https://policy.unm.edu/university-policies/table-of-contents.html';
            $title = "UAP Table of Contents";
            $name = "UAP Table of Contents";
        } elseif ($number = intval($s->getBbCode())) {
            $section = floor($number / 1000) * 1000;
            $url = sprintf(
                'https://policy.unm.edu/university-policies/%s/%s.html',
                $section,
                $number
            );
            $title = "UAP Policy $number";
            $name = "UAP $number";
        }
        if ($url) return (new A($url))
            ->addChild($s->getContent() ?? $name)
            ->setAttribute('title', $title);
        else return null;
    }

    public static function sortPages($pages)
    {
        if ($pages instanceof AbstractMappedSelect) {
            $pages = $pages->fetchAll();
        }
        usort($pages, function (AbstractPage $a, AbstractPage $b) {
            // first respect sort weight differences
            $weightDiff = $a->sortWeight() - $b->sortWeight();
            if ($weightDiff !== 0) return $weightDiff;
            // then sort by policy-ness/number/information-ness
            $aPolicy = $a instanceof PolicyPage;
            $bPolicy = $b instanceof PolicyPage;
            $aNumber = $aPolicy ? $a->policyNumber() : false;
            $bNumber = $bPolicy ? $b->policyNumber() : false;
            $aInfo = $aNumber ? strtolower($aNumber) == 'information' : false;
            $bInfo = $bNumber ? strtolower($bNumber) == 'information' : false;
            if ($aPolicy && $aNumber && $bPolicy && $bNumber) {
                // sort information items to bottom
                if ($aInfo && !$bInfo) return 1;
                if ($bInfo && !$aInfo) return -1;
                // compare by policy number
                return version_compare($aNumber, $bNumber);
            }
            // sort by name/sortName as a last resort
            return strcasecmp($a->sortName() ?? $a->name(), $b->sortName() ?? $b->name());
        });
        return $pages;
    }
}
