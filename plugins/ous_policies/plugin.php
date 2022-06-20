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
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class Policies extends AbstractPlugin
{
    function onCron_daily()
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
                $job->spawn(function () {
                    return PdfGenerator::generateSectionPDF('policies', 'UNM FHB');
                });
                $job->spawn(function () {
                    return PdfGenerator::generateSectionPDF('section_a', 'UNM FHB - Section A');
                });
                $job->spawn(function () {
                    return PdfGenerator::generateSectionPDF('section_b', 'UNM FHB - Section B');
                });
                $job->spawn(function () {
                    return PdfGenerator::generateSectionPDF('section_c', 'UNM FHB - Section C');
                });
                $job->spawn(function () {
                    return PdfGenerator::generateSectionPDF('section_d', 'UNM FHB - Section D');
                });
                $job->spawn(function () {
                    return PdfGenerator::generateSectionPDF('section_e', 'UNM FHB - Section E');
                });
                $job->spawn(function () {
                    return PdfGenerator::generateSectionPDF('section_f', 'UNM FHB - Section F');
                });
                return "Spawned jobs to generate section PDFs";
            });
        } else {
            return "PDFs already generated today";
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
