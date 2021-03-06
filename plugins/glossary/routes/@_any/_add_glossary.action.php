<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;
use DigraphCMS_Plugins\byjoby\glossary\GlossaryPage;

Cookies::required(['system', 'csrf']);

// ensure we have a UUID in the parameters
if (!Context::arg('uuid')) {
    $url = Context::url();
    $url->arg('uuid', Digraph::uuid());
    throw new RedirectException($url);
}

// validate parameter UUID
if (!Digraph::validateUUID(Context::arg('uuid') ?? '')) {
    $url = Context::url();
    $url->arg('uuid', Digraph::uuid());
    throw new RedirectException($url);
}

// ensure parameter UUID doesn't already exist
if (Pages::exists(Context::arg('uuid'))) {
    $url = Context::url();
    $url->arg('uuid', Digraph::uuid());
    throw new RedirectException($url);
}

$page = Context::page();

$name = (new Field('Page name'))
    ->setRequired(true)
    ->addTip('The name to be used when referring or linking to this page from elsewhere on the site.');

$content = (new RichContentField('Body content'))
    ->setPageUuid(Context::arg('uuid'))
    ->setRequired(true);

$form = (new FormWrapper('add-' . Context::arg('uuid')))
    ->addChild($name)
    ->addChild($content)
    ->addCallback(function () use ($name, $content) {
        // insert page
        $page = new GlossaryPage(
            [],
            [
                'uuid' => Context::arg('uuid')
            ]
        );
        $page->name($name->value());
        $page->richContent('body', $content->value());
        // insert with parent link to current context page
        $page->insert(Context::page()->uuid());
        // redirect
        Notifications::flashConfirmation('Glossary created: ' . $page->url()->html());
        throw new RedirectException($page->url_edit());
    });
$form->button()->setText('Create glossary page');

echo $form;
