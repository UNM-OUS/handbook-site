<?php

namespace DigraphCMS_Plugins\byjoby\glossary;

use DigraphCMS\DB\AbstractMappedSelect;

class TermSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return new GlossaryTerm(
            $row['page_uuid'],
            $row['name'],
            $row['link'],
            $row['body'],
            $row['created'],
            $row['created_by'],
            $row['updated'],
            $row['updated_by'],
            $row['uuid']
        );
    }
}
