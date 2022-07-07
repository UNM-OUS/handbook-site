<h1>Add comment period</h1>
<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\DateField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;

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

$form = new FormWrapper('add-' . Context::arg('uuid'));

$name = (new Field('Custom name'))
    ->addTip('Generally this can be left blank, and an automatically-generated name will be used');
$form->addChild($name);

$start = (new DateField('First day of comment period'))
    ->setRequired(true);
$form->addChild($start);

$end = (new DateField('Last day of comment period'))
    ->setRequired(true);
$end->addValidator(function () use ($start, $end) {
    if ($end->value() < $start->value()) {
        return "Last day cannot be before first day";
    }
    return null;
});
$form->addChild($end);

$body = (new RichContentField('Page content', Context::arg('uuid')))
    ->setDefault(Templates::render('policy/comment-page-default-content.php'))
    ->setRequired(true);
$form->addChild($body);

$form->addCallback(function () use ($name, $start, $end, $body) {
    $page = new CommentPage();
    $page->setUUID(Context::arg('uuid'));
    if ($name->value()) $page['custom_name'] = $name->value();
    $page->setFirstDay($start->value());
    $page->setLastDay($end->value());
    $page->richContent('body', $body->value());
    $page->insert();
    Notifications::flashConfirmation('Created comment period, please indicate the revisions that will be included in this comment period');
    throw new RedirectException($page->url('pick_revisions'));
});
$form->button()->setText('Create comment period');

echo $form;
