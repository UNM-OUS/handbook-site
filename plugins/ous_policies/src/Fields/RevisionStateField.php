<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Fields;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\RevisionState;

class RevisionStateField extends Field
{
    public function __construct(string $label = 'Revision state', array $options = [])
    {
        parent::__construct(
            $label,
            new SELECT(RevisionState::STATES)
        );
    }
}
