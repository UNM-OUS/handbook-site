<h1>Edit approval</h1>
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
use DigraphCMS_Plugins\unmous\ous_policies\Approvals\Approvals;

$approval = Approvals::get(Context::arg('uuid'), Context::pageUUID());
if (!$approval) throw new HttpError(404);

Breadcrumb::parent(new URL('manage_approvals.html'));

$form = new FormWrapper();

$hidden = (new CheckboxField('Hide this revision from public interfaces'))
    ->setDefault($approval->hidden());
$form->addChild($hidden);

$approver = (new Field('Approver'))
    ->setDefault($approval->approver())
    ->setRequired(true);
$form->addChild($approver);

$date = (new DateField('Approval date'))
    ->setDefault($approval->approved())
    ->setRequired(true);
$form->addChild($date);

$notes = (new Field('Notes', new TEXTAREA()))
    ->setDefault($approval->notes())
    ->setRequired(false);
$form->addChild($notes);

$form->addCallback(function () use ($approval, $hidden, $approver, $date, $notes) {
    $approval->setHidden($hidden->value());
    $approval->setApprover($approver->value());
    $approval->setApproved($date->value());
    $approval->setNotes($notes->value());
    $approval->update();
    Notifications::flashConfirmation('Updated approval');
    throw new RedirectException(new URL('manage_approvals.html'));
});
echo $form;
