<?php

namespace DigraphCMS_Plugins\unmous\ous_policies;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\HTML\ConditionalContainer;
use DigraphCMS\HTML\LI;
use DigraphCMS\HTML\Tag;
use DigraphCMS\HTML\UL;
use DigraphCMS\UI\Format;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

class PolicyTableOfContents extends Tag
{
    protected $tag = 'div';
    protected $page, $prefixes, $parents;
    protected $ul, $changesBlock;

    /**
     * @param AbstractPage $page
     * @param string|array $prefixes
     * @param array $parents
     */
    public function __construct(AbstractPage $page, $prefixes = '*', $parents = [])
    {
        $this->page = $page;
        $this->prefixes = is_array($prefixes) ? $prefixes : explode(',', $prefixes);
        $this->prefixes = array_filter($this->prefixes, function ($e) {
            return !!$e;
        });
        $this->prefixes = array_map('\\strtolower', $this->prefixes);
        $this->parents = $parents;
        $this->parents[] = $page->uuid();
    }

    public function classes(): array
    {
        return array_merge(
            [
                'policytoc'
            ],
            parent::classes()
        );
    }

    public function id(): ?string
    {
        return parent::id() ?? 'policytoc--' . $this->page->uuid();
    }

    public function children(): array
    {
        return array_merge(
            [
                $this->changesBlock(),
                $this->ul(),
            ],
            parent::children()
        );
    }

    protected function changesBlock(): ConditionalContainer
    {
        if (!$this->changesBlock) {
            $this->changesBlock = (new ConditionalContainer)
                ->addClass('policytoc__changes')
                ->addClass('card')
                ->addClass('card--light');
            $cacheID = 'policy/toc/changes-' . md5(serialize([
                $this->page->uuid(),
                $this->prefixes,
                $this->parents
            ]));
            $changes = Cache::get(
                $cacheID,
                function () {
                    return $this->generateChanges();
                },
                1800
            );
            foreach ($changes as $child) {
                $this->changesBlock->addChild($child);
            }
        }
        return $this->changesBlock;
    }

    protected function generateChanges(): array
    {
        if (!$this->prefixes) return [];
        $revisions = Revisions::select()
            ->publicView()
            ->where('effective <= ?', [date('Y-m-d')])
            ->where('effective >= ?', [date('Y-m-d', strtotime('-3 years'))])
            ->where("(type IN ('abolished','created') OR moved = 1)")
            ->order('effective desc, id desc');
        $alreadyListedMoves = [];
        $out = array_filter(array_map(
            function (PolicyRevision $rev) use (&$alreadyListedMoves) {
                if ($this->prefixMatches($rev->number())) {
                    // creations and abolishments only matter if prefix matches
                    if ($rev->type() == 'created') {
                        if ($rev->fullname() == $rev->policy()->name()) {
                            return sprintf(
                                '<div class="policytoc__changes__addition"><a href="%s">Added %s</a>: %s</div>',
                                $rev->url(),
                                Format::date($rev->effective()),
                                $rev->policy()->url()->html()
                            );
                        } else {
                            return sprintf(
                                '<div class="policytoc__changes__addition"><a href="%s">Added %s</a>: <strong>%s</strong> (later renamed to %s)</div>',
                                $rev->url(),
                                Format::date($rev->effective()),
                                $rev->fullName(),
                                $rev->policy()->url()->html(),
                            );
                        }
                    }
                    // abolishments also only matter if prefix matches
                    if ($rev->type() == 'created') {
                        return sprintf(
                            '<div class="policytoc__changes__abolition">Abolished %s: <a href="%s">%s</a></div>',
                            Format::date($rev->effective()),
                            $rev->url(),
                            $rev->policy()->name()
                        );
                    }
                    // if prefix matches, this revision moved the policy INTO this prefix
                    if ($rev->moved()) {
                        $previous = $rev->previousRevision();
                        if ($previous && $this->prefixMatches($previous->number())) {
                            $alreadyListedMoves[] = $previous->uuid();
                            return sprintf(
                                '<div class="policytoc__changes__rename"><a href="%s">Renamed %s</a>: <a href="%s">%s</a> (formerly <strong>%s</strong>)</div>',
                                $rev->url(),
                                Format::date($rev->effective()),
                                $rev->policy()->url(),
                                $rev->fullName(),
                                $previous->fullName()
                            );
                        } elseif($previous) {
                            return sprintf(
                                '<div class="policytoc__changes__move-in"><a href="%s">Moved %s</a>: %s (formerly <strong>%s</strong>)</div>',
                                $rev->url(),
                                Format::date($rev->effective()),
                                $rev->policy()->url()->html(),
                                $previous->fullName()
                            );
                        }
                    }
                } elseif ($rev->moved()) {
                    // we have to look at all revisions to see if anything moved OUT of this prefix
                    if (!in_array($rev->uuid(), $alreadyListedMoves)) {
                        // must not have already been listed as a move into this prefix above
                        $previous = $rev->previousRevision();
                        if ($previous && $this->prefixMatches($previous->number())) {
                            return sprintf(
                                '<div class="policytoc__changes__move-out"><a href="%s">Moved %s</a>: <strong>%s</strong> is now <a href="%s">%s</a></div>',
                                $rev->url(),
                                Format::date($rev->effective()),
                                $previous->fullName(),
                                $rev->policy()->url(),
                                $rev->fullName()
                            );
                        }
                    }
                }
                return false;
            },
            $revisions->fetchAll()
        ));
        if ($out) array_unshift($out, "<div class='policytoc__changes__header'>Recent organizational changes:</div>");
        return $out;
    }

    protected function prefixMatches($number): bool
    {
        if (!$this->prefixes) return false;
        $number = strtolower($number);
        if (!$number) return false;
        if (!preg_match('/^[a-z][0-9]/', $number)) return false;
        foreach ($this->prefixes as $prefix) {
            if ($prefix == '*') return true;
            if (substr($number, 0, strlen($prefix)) == $prefix) return true;
        }
        return false;
    }

    protected function ul(): UL
    {
        if (!$this->ul) {
            $this->ul = (new UL)
                ->addClass('policytoc__toc')
                ->addClass('table-of-contents');
            foreach ($this->generateItems() as $li) {
                $this->ul->addChild($li);
            }
        }
        return $this->ul;
    }

    protected function generateItems(): array
    {
        $cacheID = 'policy/toc/items-' . md5(serialize([
            $this->page->uuid(),
            $this->prefixes,
            $this->parents
        ]));
        return Cache::get($cacheID, function () {
            return $this->doGenerateItems();
        }, 60);
    }

    protected function doGenerateItems(): array
    {
        $children = Pages::children($this->page->uuid())->fetchAll();
        // filter things that shouldn't be in this list
        $children = array_filter(
            $children,
            function (AbstractPage $child) {
                // break cycles
                if (in_array($child->uuid(), $this->parents)) return false;
                // include by default
                return true;
            }
        );
        // sort children
        $children = Policies::sortPages($children);
        // convert to html
        return array_map(
            function (AbstractPage $child): LI {
                $li = new LI;
                $childCount = Pages::children($child->uuid())->count();
                if ($child instanceof PolicyPage) {
                    $li->addChild($child->url()->html());
                } else {
                    $li->addChild($child->url()->html());
                }
                if ($childCount) $li->addChild(trim(new PolicyTableOfContents($child, '', $this->parents)));
                return $li;
            },
            $children
        );
    }
}
