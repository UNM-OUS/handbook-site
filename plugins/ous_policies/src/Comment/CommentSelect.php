<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Comment;

use DigraphCMS\DB\AbstractMappedSelect;

class CommentSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return CommentPeriods::resultToApproval($row);
    }
}
