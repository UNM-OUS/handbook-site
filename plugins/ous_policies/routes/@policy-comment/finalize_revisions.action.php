<h1>Finalize comment period</h1>
<p>
    This tool only appears once a comment period has ended, and is used to quickly either publish or cancel the attached revisions.
</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Fields\DateField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_policies\Comment\CommentPage;
use DigraphCMS_Plugins\unmous\ous_policies\Fields\RevisionStateField;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;

/** @var CommentPage */
$page = Context::page();
$revisions = $page->revisions();

$table = new PaginatedTable(
    $revisions,
    function (PolicyRevision $revision): array {
        $interface = '<div class="navigation-frame navigation-frame--stateless" data-target="_frame" id="set-state-' . $revision->uuid() . '">';
        $form = new FormWrapper($revision->uuid());
        $form->button()->setText('Save revision');
        $state = (new RevisionStateField('Revision state'))
            ->setDefault($revision->state()->__toString())
            ->setRequired(true);
        $form->addChild($state);
        $date = (new DateField('Effective date'))
            ->setDefault($revision->effective());
        $date->addValidator(function () use ($state, $date) {
            if ($state->value() == 'published' && !$date->value()) {
                return 'An effective date is required to publish';
            } else return null;
        });
        $form->addChild($date);
        if ($form->ready()) {
            $revision->setState($state->value());
            $revision->setEffective($date->value());
            $revision->update();
        }
        $interface .= $form;
        $interface .= '</div>';
        return [
            sprintf('<a href="%s" target="_top">%s<br>%s</a>', $revision->url(), $revision->fullName(), $revision->metaTitle()),
            $interface
        ];
    }
);

echo $table;
