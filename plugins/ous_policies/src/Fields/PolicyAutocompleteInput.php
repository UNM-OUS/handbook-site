<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Fields;

use DigraphCMS\HTML\Forms\Fields\Autocomplete\PageInput;
use DigraphCMS\URL\URL;

class PolicyAutocompleteInput extends PageInput
{
    public function __construct(string $id = null, URL $endpoint = null)
    {
        parent::__construct($id, $endpoint ?? new URL('/~api/v1/autocomplete/page.php?class=policy'));
    }
}
