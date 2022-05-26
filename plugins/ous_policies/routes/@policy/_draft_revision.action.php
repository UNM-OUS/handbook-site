<h1>Draft revision</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\DateField;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\Fields\RevisionTypeField;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

Breadcrumb::parent(new URL('manage_revisions.html'));

// set up form
$form = new FormWrapper('draft_revision_' . Context::pageUUID());
$form->button()->setText('Create draft');

$type = (new RevisionTypeField())
    ->setDefault('firstweb')
    ->setRequired(true);
$form->addChild($type);

$moved = (new CheckboxField('This revision moves or renames the policy'));
$form->addChild($moved);

$effective = (new DateField('Effective date'))
    ->addTip('Should usually be left blank for revisions that are still under review in some way.')
    ->setDefault(new DateTime());
$form->addChild($effective);

$title = (new Field('Custom revision title'))
    ->addTip('Title to be used when referring to this revision on the revision logs or public comment systems')
    ->addTip('Should be left blank unless a special name is needed for some reason, so that standard names can be used and automatically updated');
$form->addChild($title);

// list and handle rich media for cloning/linking
$clones = [];
if (Context::arg('from') && $from = Revisions::get(Context::arg('from'), Context::pageUUID())) {
    $media = RichMedia::select($from->uuid());
    if ($media->count()) {
        $group = new FIELDSET('Create new copies of rich media');
        $group->addChild('<p><small>By default rich media is not copied, and the new revision will continue to reference the rich media attached to the original revision. Check any rich media you would like to clone a copy of for the new page instead. The system will attempt to automatically update any embed tags to point to the new cloned media.</small></p>');
        foreach ($media as $m) {
            $group->addChild(
                $clones[$m->uuid()] = new CheckboxField($m->className() . ': ' . $m->name())
            );
        }
        $form->addChild($group);
    }
}

// check if there's a previous revision
if (Context::arg('from')) {
    $type->setDefault('minor');
}

// handle form
$form->addCallback(function () use ($clones, $title, $type, $moved, $effective) {
    $revision = new PolicyRevision(
        $title->value(),
        Context::pageUUID(),
        Context::page()->policyNumber(),
        Context::page()->policyName(),
        $effective->value(),
        $type->value(),
        $moved->value(),
        'draft',
        []
    );
    // set values from existing revision if specified
    if (Context::arg('from') && $from = Revisions::get(Context::arg('from'), Context::pageUUID())) {
        $body = $from->body()->source();
        // clone media
        $clonedMedia = [];
        foreach ($clones as $uuid => $field) {
            if ($field->value()) {
                $clonedMedia[$uuid] = clone RichMedia::get($uuid);
                $clonedMedia[$uuid]->setUUID(Digraph::uuid('m'));
                $clonedMedia[$uuid]->setParent($revision->uuid());
                $clonedMedia[$uuid]->insert();
            }
        }
        // find/replace changed media UUIDs in body
        foreach ($clonedMedia as $oldUUID => $newMedia) {
            $body = str_replace($oldUUID, $newMedia->uuid(), $body);
        }
        // set values from existing revision
        $revision->setName($from->name());
        $revision->setNumber($from->number());
        $revision->setBody(new RichContent($body));
    } else {
        $revision->setBody(new RichContent(sprintf(
            '# %s%s',
            $revision->number() ? $revision->number() . ': ' : '',
            $revision->name()
        )));
    }
    // insert revision
    $revision->insert();
    Notifications::flashConfirmation('Draft revision inserted, ready to complete editing');
    throw new RedirectException(
        new URL('_edit_revision.html?uuid=' . $revision->uuid())
    );
});
echo $form;
