<h1>Create comment period</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\DateField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPeriods;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\RevisionComment;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

$uuid = Context::arg('uuid');
if (!$uuid || !Digraph::validateUUID($uuid, 'pubcom') || CommentPeriods::get($uuid)) {
    $uuid = Digraph::uuid('pubcom');
    throw new RedirectException(new URL('&uuid=' . $uuid));
}

$revision = Revisions::get(Context::arg('revision'), Context::pageUUID());
if (!$revision) throw new HttpError(401);

$form = new FormWrapper();

$title = (new Field('Comment period title'))
    ->setRequired(true)
    ->setDefault('Updates to ' . $revision->fullName());
$form->addChild($title);

$start = (new DateField('Start date'))
    ->addTip('Comment period will appear on the public site shortly after midnight on this date')
    ->setRequired(true);
$form->addChild($start);

$end = (new DateField('End date'))
    ->addTip('Comment period will move off the list of current comment periods shortly after midnight after this day ends')
    ->setRequired(true);
$form->addChild($end);

$notes = (new RichContentField('Public notes about change', $uuid));
$form->addChild($notes);

if ($form->ready()) {
    $comment = new RevisionComment(
        Context::arg('revision'),
        $start->value(),
        $end->value(),
        $title->value(),
        $notes->value(),
        $uuid
    );
    $comment->insert();
    throw new RedirectException($comment->url());
}

$form->button()->setText('Create comment period');
echo $form;
