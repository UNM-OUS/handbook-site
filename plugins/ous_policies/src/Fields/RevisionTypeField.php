<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Fields;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\RevisionType;

class RevisionTypeField extends Field
{
    public function __construct(string $label = 'Revision type', array $options = [])
    {
        parent::__construct(
            $label,
            new SELECT(RevisionType::TYPES)
        );
    }
}
