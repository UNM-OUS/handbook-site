<h1>Add glossary term</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

$form = new FormWrapper();
$form->button()->setText('Add term');
$name = (new Field('Glossary term'))
    ->setRequired(true);
$form->addChild($name);
$body = (new RichContentField('Card content', Context::pageUUID(), true))

    ->setRequired(true);
$form->addChild($body);
$url = (new Field('Link URL'))
    ->addTip('Matching text will automatically be linked to this URL, if provided.');
$form->addChild($url);

if ($form->ready()) {
    try {
        DB::beginTransaction();
        DB::query()->insertInto(
            'glossary_term',
            [
                'uuid' => $uuid = Digraph::uuid(),
                'page_uuid' => Context::pageUUID(),
                'name' => $name->value(),
                'link' => $url->value() ? $url->value() : null,
                'body' => $body->value()->source(),
                'created' => time(),
                'created_by' => Session::uuid(),
                'updated' => time(),
                'updated_by' => Session::uuid(),
            ]
        )->execute();
        DB::query()->insertInto(
            'glossary_pattern',
            [
                'glossary_term_uuid' => $uuid,
                'pattern' => strtolower($name->value())
            ]
        )->execute();
        DB::commit();
        Notifications::flashConfirmation('Added glossary term');
    } catch (\Throwable $th) {
        DB::rollback();
        Notifications::flashError('Error: ' . $th->getMessage());
    }
    throw new RedirectException(new URL('manage_terms.html'));
}

echo $form;