<h1>Edit revision</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\DateField;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\Fields\RevisionStateField;
use DigraphCMS_Plugins\unmous\ous_policies\Fields\RevisionTypeField;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

$revision = Revisions::get(Context::arg('uuid'), Context::pageUUID());
if (!$revision) throw new HttpError(404);

$uuid = $revision->uuid();
Breadcrumb::parent($revision->url());
ActionMenu::addContextAction(new URL("_edit_revision.html?uuid=$uuid"));
ActionMenu::addContextAction(new URL("_delete_revision.html?uuid=$uuid"));

// set up form
$form = new FormWrapper('edit_policy_revision_' . Context::arg('uuid'));
$form->button()->setText('Save revision');

$metadata = new FIELDSET('Metadata');

$state = (new RevisionStateField)
    ->setDefault($revision->state()->__toString())
    ->setRequired(true);
$metadata->addChild($state);

$type = (new RevisionTypeField())
    ->setDefault($revision->type()->__toString())
    ->setRequired(true);
$metadata->addChild($type);

$moved = (new CheckboxField('This revision moves or renames the policy'))
    ->setDefault($revision->moved());
$metadata->addChild($moved);

$effective = (new DateField('Effective date'))
    ->addTip('Should usually be left blank for revisions that are still under review in some way.')
    ->setDefault($revision->effective());
$metadata->addChild($effective);

$title = (new Field('Custom revision title'))
    ->setDefault($revision->title(true))
    ->addTip('Title to be used when referring to this revision on the revision logs or public comment systems')
    ->addTip('Should be left blank unless a special name is needed for some reason, so that standard names can be used and automatically updated');
$metadata->addChild($title);

$notes = (new RichContentField('Revision notes', Context::arg('uuid') . '_notes'))
    ->setDefault($revision->notes())
    ->addTip('Notes to be displayed when showing this revision on the revision logs or public comment systems');
$metadata->addChild($notes);

$policyContent = new FIELDSET('Policy content');

$number = (new Field('Policy number'))
    ->setDefault($revision->number())
    ->addTip('Can be left blank if you would like to use the policy versioning system for something that isn\'t a policy.');
$policyContent->addChild($number);

$name = (new Field('Policy name'))
    ->setDefault($revision->name())
    ->setRequired(true);
$policyContent->addChild($name);

$body = (new RichContentField('Policy body', Context::arg('uuid')))
    ->setDefault($revision->body())
    ->setRequired(true);
$policyContent->addChild($body);

$form->addChild($metadata);
$form->addChild($policyContent);

// handle form
$form->addCallback(function () use ($revision, $title, $moved, $notes, $state, $type, $number, $name, $effective, $body) {
    $revision->setTitle($title->value())
        ->setState($state->value())
        ->setType($type->value())
        ->setNumber($number->value())
        ->setName($name->value())
        ->setEffective($effective->value())
        ->setBody($body->value())
        ->setNotes($notes->value())
        ->setMoved($moved->value())
        ->update();
    Notifications::flashConfirmation('Revision updated');
    throw new RedirectException(new URL('manage_revisions.html'));
});

echo $form;
