<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Comment;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\RichContent\RichContent;

class CommentPeriods
{
    protected static $cache = [];

    public static function get(?string $uuid): ?RevisionComment
    {
        return static::select()
            ->where('uuid = ?', [$uuid])
            ->fetch();
    }

    public static function select(string $revisionUUID = null): CommentSelect
    {
        $query = new CommentSelect(
            DB::query()->from('ous_revision_comment')
        );
        if ($revisionUUID) $query->where('revision_uuid = ?', [$revisionUUID]);
        return $query;
    }

    public static function update(RevisionComment $comment)
    {
        return DB::query()
            ->update('ous_revision_comment')
            ->where('uuid = ?', [$comment->uuid()])
            ->set(
                [
                    'start' => $comment->end()->format('Y-m-d'),
                    'end' => $comment->end()->format('Y-m-d'),
                    'name' => $comment->name(),
                    'notes' => json_encode($comment->notes()->array()),
                    'updated' => $comment->updated()->getTimestamp(),
                    'updated_by' => $comment->updatedByUUID()
                ]
            )->execute();
    }

    public static function delete(RevisionComment $comment)
    {
        return DB::query()
            ->deleteFrom('ous_revision_comment')
            ->where('uuid = ?', [$comment->uuid()])
            ->execute();
    }

    public static function insert(RevisionComment $comment)
    {
        return DB::query()->insertInto(
            'ous_revision_comment',
            [
                'uuid' => $comment->uuid(),
                'revision_uuid' => $comment->revisionUUID(),
                'start' => $comment->end()->format('Y-m-d'),
                'end' => $comment->end()->format('Y-m-d'),
                'name' => $comment->name(),
                'notes' => json_encode($comment->notes()->array()),
                'created' => $comment->created()->getTimestamp(),
                'created_by' => $comment->createdByUUID(),
                'updated' => $comment->updated()->getTimestamp(),
                'updated_by' => $comment->updatedByUUID()
            ]
        )->execute();
    }

    public static function resultToApproval(array $result): ?RevisionComment
    {
        if (!is_array($result)) {
            return null;
        }
        if (isset(static::$cache[$result['uuid']])) {
            return static::$cache[$result['uuid']];
        }
        $notes = json_decode($result['notes']);
        if ($notes === false) {
            throw new \Exception("Failed to decode JSON for notes for comment period");
        }
        return static::$cache[$result['uuid']] = new RevisionComment(
            $result['revision_uuid'],
            DateTime::createFromFormat('Y-m-d', $result['start']),
            DateTime::createFromFormat('Y-m-d', $result['end']),
            $result['name'],
            new RichContent($notes),
            $result['uuid'],
            $result['created'],
            $result['created_by'],
            $result['updated'],
            $result['updated_by'],
        );
    }
}
