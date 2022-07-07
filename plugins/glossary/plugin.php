<?php

namespace DigraphCMS_Plugins\byjoby\glossary;

use DigraphCMS\Cache\Cache;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\Response;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\UI\Format;
use DOMElement;
use DOMNode;
use DOMText;
use Masterminds\HTML5;

class Glossary extends AbstractPlugin
{
    public function onTemplateWrapResponse(Response $response)
    {
        $response->content(
            static::parseHTML($response->content())
        );
    }

    public static function parseHTML(string $html): string
    {
        return Cache::get(
            'glossary/html/' . md5($html),
            function () use ($html) {
                if (!static::allPatterns()) return $html;
                $html5 = new HTML5();
                $fragment = $html5->parseFragment($html);
                $matched = [];
                static::parseElement($fragment, $matched);
                return $html5->saveHTML($fragment);
            },
            3600
        );
    }

    public static function selectTerms(): TermSelect
    {
        return new TermSelect(DB::query()->from('glossary_term'));
    }

    public static function get(string $uuid = null): ?GlossaryTerm
    {
        if (!$uuid) return null;
        $result = static::selectTerms()->where('uuid = ?', [$uuid])->fetch();
        return $result ? $result : null;
    }

    protected static function parseText(string $text, array &$matched = [])
    {
        return preg_replace_callback(
            static::completeRegexPattern(),
            function ($m) use (&$matched) {
                // check if we've already matched this term
                if (in_array(strtolower($m[0]), $matched)) return $m[0];
                $matched[] = strtolower($m[0]);
                // determine that term exists
                $term = static::firstMatch($m[0]);
                if (!$term) return $m[0];
                return sprintf(
                    '<a class="glossary-term">%s%s</a>',
                    $m[0],
                    Format::base64obfuscate('<div class="glossary-term__card">' . $term->cardContent() . '</div>')
                );
            },
            $text
        );
    }

    protected static function firstMatch(string $term): ?GlossaryTerm
    {
        foreach (static::allPatterns() as list($pattern, $termID)) {
            if (preg_match('/\b' . $pattern . '\b/i', $term)) return static::get($termID);
        }
        return null;
    }

    protected static function completeRegexPattern(): string
    {
        return '/\b(' . implode('|', array_map(
            function ($e) {
                return $e[0];
            },
            static::allPatterns()
        )) . ')\b/i';
    }

    protected static function allPatterns(): array
    {
        return Cache::get(
            'glossary/allpatterns',
            function () {
                $patterns = array_map(
                    function ($row) {
                        if ($row['regex']) $pattern = $row['pattern'];
                        else $pattern = preg_quote($row['pattern']);
                        return [
                            $pattern,
                            $row['glossary_term_uuid']
                        ];
                    },
                    DB::query()
                        ->from('glossary_pattern')
                        ->fetchAll()
                );
                usort($patterns, function ($a, $b) {
                    return strlen($b[0]) - strlen($a[0]);
                });
                return $patterns;
            },
            3600
        );
    }

    public static function parseElement(DOMNode $element, array &$matched = [])
    {
        if ($element instanceof DOMText) {
            // do parsing of text
            $newText = static::parseText($element->textContent, $matched);
            if ($newText != $element->textContent) {
                $newChild = $element->ownerDocument->createDocumentFragment();
                $newChild->appendXML($newText);
                $element->parentNode->replaceChild($newChild, $element);
            }
        } elseif ($element instanceof DOMElement) {
            // allow data-no-glossary attribute to skip glossary searching in an element
            if ($element->getAttribute('data-no-glossary')) return;
            // don't search inside some elements
            if (in_array($element->tagName, ['a', 'select', 'pre', 'textarea', 'form', 'code', 'input'])) return;
            // don't search in some classes
            $classes = explode(' ', $element->getAttribute('class') ?? '');
            if (array_intersect($classes, ['form-wrapper', 'form-field', 'menubar', 'notification'])) return;
            // reset matches on some elements
            if (in_array($element->tagName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) $matched = [];
        }
        // recurse if possible
        if ($element->hasChildNodes()) {
            $children = [];
            foreach ($element->childNodes as $child) {
                $children[] = $child;
            }
            //loop through new array of child nodes
            foreach ($children as $child) {
                static::parseElement($child, $matched);
            }
        }
    }
}
