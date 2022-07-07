<h1>Edit comment period</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\DateField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\Notifications;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;

/** @var CommentPage */
$page = Context::page();

$form = new FormWrapper('edit-' . Context::pageUUID());

$name = (new Field('Custom name'))
    ->setAttribute('placeholder', $page->defaultName())
    ->setDefault($page['custom_name'])
    ->addTip('Generally this can be left blank, and an automatically-generated name will be used');
$form->addChild($name);

$start = (new DateField('First day of comment period'))
    ->setDefault($page->firstDay())
    ->setRequired(true);
$form->addChild($start);

$end = (new DateField('Last day of comment period'))
    ->setDefault($page->lastDay())
    ->setRequired(true);
$end->addValidator(function () use ($start, $end) {
    if ($end->value() < $start->value()) {
        return "Last day cannot be before first day";
    }
    return null;
});
$form->addChild($end);

$body = (new RichContentField('Page content', $page->uuid()))
    ->setDefault($page->richContent('body'))
    ->setRequired(true);
$form->addChild($body);

$form->addCallback(function () use ($page, $name, $start, $end, $body) {
    $page->setFirstDay($start->value());
    if ($name->value()) $page['custom_name'] = $name->value();
    else unset($page['custom_name']);
    $page->setLastDay($end->value());
    $page->richContent('body', $body->value());
    $page->update();
    Notifications::flashConfirmation('Updated comment period');
    throw new RedirectException($page->url());
});
$form->button()->setText('Update comment period');

echo $form;
