<h1>Policy revision history</h1>
<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Spreadsheets\CellWriters\DateCell;
use DigraphCMS\Spreadsheets\CellWriters\LinkCell;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_policies\Fields\PolicyAutocompleteInput;
use DigraphCMS_Plugins\unmous\ous_policies\PolicyPage;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

Context::response()->setSearchIndex(!Context::url()->query());
$form = new FormWrapper();

$showMinor = (new CheckboxField('Include minor/maintenance revisions in results'))
    ->addTip('Optionally include minor and maintenance revisions to the website, such as updating formatting or fixing links')
    ->setDefault(!!Context::arg('show_minor'));
$form->addChild($showMinor);
$onlyPolicy = (new Field('Search for all revisions to a policy', new PolicyAutocompleteInput()))
    ->addTip('If you would like to only see revisions to a specific policy, please search for and select it here')
    ->setDefault(Context::arg('only_policy'));
$form->addChild($onlyPolicy);
$form->button()->setText('Update revision display');

$form
    ->addCallback(function () use ($showMinor, $onlyPolicy) {
        $url = Context::url();
        if ($onlyPolicy->value()) $url->arg('only_policy', $onlyPolicy->value());
        else $url->unsetArg('only_policy');
        if ($showMinor->value()) $url->arg('show_minor', '1');
        else $url->unsetArg('show_minor');
        throw new RedirectException($url);
    })
    ->addClass('collapsible')
    ->setData('collapsible-name', 'advanced options');

echo $form;

$filenameSuffix = '';
if (Context::arg('show_minor') || Context::arg('only_policy')) {
    Breadcrumb::parent(new URL('/policy_updates/'));
    Breadcrumb::setTopName('Custom display');
    $notes = [];
    if (Context::arg('only_policy')) {
        if (($policy = Pages::get(Context::arg('only_policy'))) && $policy instanceof PolicyPage) {
            $notes[] = "<strong>Only displaying revisions of: </strong> " . $policy->url()->html();
            $filenameSuffix .= ' - ' . $policy->name();
        } else {
            throw new HttpError(401);
        }
    }
    if (Context::arg('show_minor')) {
        $notes[] = "<strong>Displaying minor/maintenance revisions</strong>";
        $filenameSuffix .= ' - minor_included';
    }
    echo '<p>';
    echo implode('<br>', $notes);
    echo '</p>';
}

$revisions = Revisions::select()
    ->where('state = "published"')
    ->where('effective is not null')
    ->where('effective <= ?', [date('Y-m-d')])
    ->order('effective DESC');

if (!Context::arg('show_minor')) {
    $revisions->where('type <> "minor"');
    $revisions->where('type <> "firstweb"');
}

if (Context::arg('only_policy')) {
    $revisions->where('page_uuid = ?', [Context::arg('only_policy')]);
}

$table = new PaginatedTable(
    $revisions,
    function (PolicyRevision $revision): array {
        return [
            sprintf(
                '<a href="%s">%s</a>',
                $revision->policy()->url(),
                $revision->fullName()
            ),
            sprintf(
                '<a href="%s">%s</a>',
                $revision->url(),
                $revision->title()
            ),
            $revision->effective() ? Format::date($revision->effective()) : ''
        ];
    },
    [
        new ColumnSortingHeader('Policy', 'num', $revisions),
        new ColumnHeader('Revision information'),
        new ColumnSortingHeader('Effective', 'effective', $revisions)
    ]
);

$table->download(
    preg_replace('/[^a-z0-9\-\_ ]/i', '_', 'FHB revisions' . $filenameSuffix . ' - ' . date('Y-m-d')),
    function (PolicyRevision $revision): array {
        return [
            new LinkCell($revision->fullName(), $revision->policy()->url()),
            new LinkCell($revision->title(), $revision->url()),
            new DateCell($revision->effective()),
            $revision->type()->__toString()
        ];
    },
    [
        'Policy',
        'Revision information',
        'Effective',
        'Revision type',
    ]
);

echo $table;
