<h1>Add approval</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\DateField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\Approvals\RevisionApproval;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

$revision = Revisions::get(Context::arg('revision'), Context::pageUUID());
if (!$revision) throw new HttpError(404);

Breadcrumb::parent(new URL('manage_approvals.html'));

$form = new FormWrapper();

$hidden = (new CheckboxField('Hide this revision from public interfaces'));
$form->addChild($hidden);

$approver = (new Field('Approver'))
    ->setRequired(true);
$form->addChild($approver);

$date = (new DateField('Approval date'))
    ->setRequired(true);
$form->addChild($date);

$notes = (new Field('Notes', new TEXTAREA()))
    ->setRequired(false);
$form->addChild($notes);

$form->addCallback(function () use ($revision, $hidden, $approver, $date, $notes) {
    $approval = new RevisionApproval(
        $revision->uuid(),
        $approver->value(),
        $date->value(),
        $notes->value() ?? '',
        $hidden->value()
    );
    $approval->insert();
    Notifications::flashConfirmation('Added approval to ' . $revision->metaTitle());
    throw new RedirectException(new URL('manage_approvals.html'));
});
echo $form;
