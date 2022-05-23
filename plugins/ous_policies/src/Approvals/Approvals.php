<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Approvals;

use DateTime;
use DigraphCMS\DB\DB;

class Approvals
{
    protected static $cache = [];

    public static function get(?string $uuid): ?RevisionApproval
    {
        return static::select()
            ->where('uuid = ?', [$uuid])
            ->fetch();
    }

    public static function select(string $revisionUUID = null): ApprovalSelect
    {
        $query = new ApprovalSelect(
            DB::query()->from('ous_revision_approval')
        );
        if ($revisionUUID) $query->where('revision_uuid = ?', [$revisionUUID]);
        return $query;
    }

    public static function update(RevisionApproval $approval)
    {
        return DB::query()
            ->update('ous_revision_approval')
            ->where('uuid = ?', [$approval->uuid()])
            ->set(
                [
                    'approver' => $approval->approver(),
                    'approved' => $approval->approved()->format('Y-m-d'),
                    'notes' => $approval->notes(),
                    'hidden' => $approval->hidden(),
                    'updated' => $approval->updated()->getTimestamp(),
                    'updated_by' => $approval->updatedByUUID()
                ]
            )->execute();
    }

    public static function delete(RevisionApproval $approval)
    {
        return DB::query()
            ->deleteFrom('ous_revision_approval')
            ->where('uuid = ?', [$approval->uuid()])
            ->execute();
    }

    public static function insert(RevisionApproval $approval)
    {
        return DB::query()->insertInto(
            'ous_revision_approval',
            [
                'uuid' => $approval->uuid(),
                'revision_uuid' => $approval->revisionUUID(),
                'approver' => $approval->approver(),
                'approved' => $approval->approved()->format('Y-m-d'),
                'notes' => $approval->notes(),
                'hidden' => $approval->hidden(),
                'created' => $approval->created()->getTimestamp(),
                'created_by' => $approval->createdByUUID(),
                'updated' => $approval->updated()->getTimestamp(),
                'updated_by' => $approval->updatedByUUID()
            ]
        )->execute();
    }

    public static function resultToApproval(array $result): ?RevisionApproval
    {
        if (!is_array($result)) {
            return null;
        }
        if (isset(static::$cache[$result['uuid']])) {
            return static::$cache[$result['uuid']];
        }
        return static::$cache[$result['uuid']] = new RevisionApproval(
            $result['revision_uuid'],
            $result['approver'],
            DateTime::createFromFormat('Y-m-d', $result['approved']),
            $result['notes'],
            !!$result['hidden'],
            $result['uuid'],
            $result['created'],
            $result['created_by'],
            $result['updated'],
            $result['updated_by'],
        );
    }
}
