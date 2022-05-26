<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Revisions;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\Session\Session;
use Exception;

class Revisions
{
    protected static $cache = [];

    public static function insert(PolicyRevision $revision)
    {
        return DB::query()
            ->insertInto(
                'ous_policy_revision',
                [
                    'uuid' => $revision->uuid(),
                    'page_uuid' => $revision->pageUUID(),
                    'num' => $revision->number(),
                    'name' => $revision->name(),
                    'title' => $revision->title(true),
                    'effective' => $revision->effective() ? $revision->effective()->format('Y-m-d') : null,
                    'type' => $revision->type()->__toString(),
                    'moved' => $revision->moved(),
                    'state' => $revision->state()->__toString(),
                    'data' => json_encode($revision->get()),
                    'created' => $revision->created()->getTimestamp(),
                    'created_by' => Session::uuid(),
                    'updated' => $revision->updated()->getTimestamp(),
                    'updated_by' => Session::uuid()
                ]
            )
            ->execute();
    }

    public static function update(PolicyRevision $revision)
    {
        return DB::query()
            ->update('ous_policy_revision')
            ->where('uuid = ?', [$revision->uuid()])
            ->set([
                'num' => $revision->number(),
                'name' => $revision->name(),
                'title' => $revision->title(true),
                'effective' => $revision->effective() ? $revision->effective()->format('Y-m-d') : null,
                'type' => $revision->type()->__toString(),
                'moved' => $revision->moved(),
                'state' => $revision->state()->__toString(),
                'data' => json_encode($revision->get()),
                'updated' => time(),
                'updated_by' => Session::uuid()
            ])
            ->execute();
    }

    public static function delete(PolicyRevision $revision)
    {
        DB::beginTransaction();
        // delete approvals
        DB::query()
            ->deleteFrom('ous_revision_approval')
            ->where('revision_uuid = ?', [$revision->uuid()])
            ->execute();
        // delete associated rich media
        foreach (RichMedia::select($revision->uuid()) as $media) {
            $media->delete();
        }
        // delete revision
        DB::query()
            ->deleteFrom('ous_policy_revision')
            ->where('uuid = ?', [$revision->uuid()])
            ->execute();
        // commit transaction
        DB::commit();
    }

    public static function get(?string $uuid, string $page_uuid = null): ?PolicyRevision
    {
        return static::select($page_uuid)
            ->where('uuid = ?', [$uuid])
            ->fetch();
    }

    public static function exists(?string $uuid, string $page_uuid = null): bool
    {
        return !!static::select($page_uuid)
            ->where('uuid = ?', [$uuid])
            ->count();
    }

    public static function select(string $page_uuid = null): RevisionSelect
    {
        $query = DB::query()->from('ous_policy_revision');
        if ($page_uuid) {
            $query->where('page_uuid = ?', [$page_uuid]);
        }
        return new RevisionSelect($query);
    }

    public static function resultToRevision(array $result): ?PolicyRevision
    {
        if (!is_array($result)) {
            return null;
        }
        if (isset(static::$cache[$result['uuid']])) {
            return static::$cache[$result['uuid']];
        }
        if (false === ($data = json_decode($result['data'], true))) {
            throw new \Exception("Error decoding PolicyRevision json data");
        }
        return static::$cache[$result['uuid']] = new PolicyRevision(
            $result['title'],
            $result['page_uuid'],
            $result['num'],
            $result['name'],
            $result['effective'] ? DateTime::createFromFormat('Y-m-d', $result['effective']) : null,
            $result['type'],
            !!$result['moved'],
            $result['state'],
            $data,
            $result['uuid'],
            $result['created'],
            $result['created_by'],
            $result['updated'],
            $result['updated_by'],
        );
    }
}
