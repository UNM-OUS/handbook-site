<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS_Plugins\unmous\ous_policies\PolicyPage;

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

$form->addChild('<div class="notification notification--notice">Policies must have an initial number and name set, so that a URL can be assigned to them and they can appear appropriately in tables of contents. Whatever you set here will later be overwritten automatically through the revisions system.</div>');

$number = (new Field('Initial policy number'))
    ->addTip('Can be left blank if you would like to use the policy versioning system for something that isn\'t a policy.');
$form->addChild($number);

$name = (new Field('Initial policy name'))
    ->addTip('Policy name and number will be updated as revisions are added, but an initial value must be set here.')
    ->setRequired(true);
$form->addChild($name);

$form->addCallback(function () use ($name, $number) {
    $policy = new PolicyPage();
    $policy->setUUID(Context::arg('uuid'));
    $policy->setPolicyNumber($number->value());
    $policy->name($name->value());
    $policy->insert(Context::pageUUID());
    Notifications::flashConfirmation('Created new policy');
    throw new RedirectException($policy->url('_draft_revision'));
});
$form->button()->setText('Create policy');

echo $form;
