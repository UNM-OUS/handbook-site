<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Revisions;

use DigraphCMS\DB\AbstractMappedSelect;

/**
 * @method PolicyRevision|null fetch()
 */
class RevisionSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return Revisions::resultToRevision($row);
    }

    /**
     * Limit query to only select publicly-visible revisions (i.e. not draft or hidden)
     *
     * @return $this
     */
    public function publicView()
    {
        $this->where('state <> "hidden" AND state <> "draft" AND state <> "cancelled"');
        return $this;
    }
}
