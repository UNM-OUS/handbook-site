<?php

namespace DigraphCMS_Plugins\unmous\ous_policies;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;
use DigraphCMS\Plugins\AbstractPlugin;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class Policies extends AbstractPlugin
{

    public function onShortCode_policytoc(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode() ?? Context::pageUUID());
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

    public function onShortCode_rpm(ShortcodeInterface $s): ?string
    {
        $url = null;
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
                $name = "RPM Section $section.$number";
            } else {
                $url = sprintf(
                    'https://policy.unm.edu/regents-policies/section-%s/index.html',
                    $section
                );
                $name = "RPM Section $section";
            }
        } elseif ($s->getBbCode() == 'preface') {
            $url = 'https://policy.unm.edu/regents-policies/index.html';
            $name = "RPM Preface";
        } elseif ($s->getBbCode() == 'toc') {
            $url = 'https://policy.unm.edu/regents-policies/table-of-contents.html';
            $name = "RPM Table of Contents";
        } elseif ($s->getBbCode() == 'foreword') {
            $url = 'https://policy.unm.edu/regents-policies/foreword.html';
            $name = "RPM Foreword";
        }
        return (new A($url, '_blank'))
            ->addChild($s->getContent() ?? $name)
            ->setAttribute('title', $name);
        return null;
    }

    public function onShortCode_uap(ShortcodeInterface $s): ?string
    {
        $url = null;
        $name = 'UAP Policy';
        if ($s->getBbCode() == 'preface') {
            $url = 'https://policy.unm.edu/university-policies/index.html';
            $name = "UAP Preface";
        } elseif ($s->getBbCode() == 'toc') {
            $url = 'https://policy.unm.edu/university-policies/table-of-contents.html';
            $name = "UAP Table of Contents";
        } elseif ($number = intval($s->getBbCode())) {
            $section = floor($number / 1000) * 1000;
            $url = sprintf(
                'https://policy.unm.edu/university-policies/%s/%s.html',
                $section,
                $number
            );
            $name = "UAP Policy $number";
        }
        if ($url) return (new A($url, '_blank'))
            ->addChild($s->getContent() ?? $name)
            ->setAttribute('title', $name);
        else return null;
    }

    public static function sortPages($pages)
    {
        if ($pages instanceof AbstractMappedSelect) {
            $pages = $pages->fetchAll();
        }
        usort($pages, function (AbstractPage $a, AbstractPage $b) {
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
            // sort by name by default
            return strcmp($a->name(), $b->name());
        });
        return $pages;
    }
}
