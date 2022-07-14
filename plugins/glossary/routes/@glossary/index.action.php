<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\byjoby\glossary\Glossary;
use DigraphCMS_Plugins\byjoby\glossary\GlossaryPage;
use DigraphCMS_Plugins\byjoby\glossary\GlossaryTerm;

Context::response()->enableCache();

/** @var GlossaryPage */
$page = Context::page();

echo $page->richContent('body');

$frameID = 'glossary-search--' . $page->uuid();

echo "<div id='$frameID' class='navigation-frame' data-target='_top' data-no-glossary='true'>";

$form = new FormWrapper('q');
$form->setData('target', $frameID);
$form->addClass('inline-autoform');
$form->button()->setText('Search');
$form->setMethod('get');
$form->token()->setCSRF(false);
$q = new INPUT('t');
$q->setAttribute('placeholder', 'Search terms');
$form->addChild($q);
echo $form;

$terms = Glossary::selectTerms()
    ->where('page_uuid = ?', [$page->uuid()])
    ->order('name asc');

if ($q->value()) {
    $words = array_filter(
        array_map('\\trim', explode(' ', $q->value())),
        function ($word) {
            return strlen($word) > 2;
        }
    );
    if ($words) {
        Breadcrumb::setTopName('Search results');
        Breadcrumb::parent($page->url());
    }
    foreach ($words as $word) {
        $terms->where('(name like ? OR body like ?)', ["%$word%", "%$word%"]);
    }
}

$table = new PaginatedTable(
    $terms,
    function (GlossaryTerm $term): array {
        return [
            $term->cardContent()
        ];
    },
    []
);
echo $table;

echo "</div>";
