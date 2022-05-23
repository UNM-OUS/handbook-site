<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Approvals;

use DigraphCMS\DB\AbstractMappedSelect;

class ApprovalSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return Approvals::resultToApproval($row);
    }

    /**
     * Remove hidden approvals from query
     *
     * @return $this
     */
    public function noHidden()
    {
        return $this->where('hidden = 0');
    }
}
