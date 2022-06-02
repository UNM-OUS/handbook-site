<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Comment;

use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

class RevisionComment
{
    /** @var string */
    protected $start, $end;
    protected $revision_uuid, $title, $notes;
    protected $uuid, $created, $created_by, $updated, $updated_by;

    public function __construct(
        string $revision_uuid = null,
        DateTime $start = null,
        DateTime $end = null,
        string $title = null,
        ?RichContent $notes = null,
        string $uuid = null,
    ) {
        $this->revision_uuid = $this->revision_uuid ?? $revision_uuid;
        $this->start = $start ? $start->format('Y-m-d') : ($this->start ?? date('Y-m-d'));
        $this->end = $end ? $end->format('Y-m-d') : ($this->end ?? date('Y-m-d'));
        $this->title = $title ?? $this->title;
        if ($notes) $this->setNotes($notes);
        else $this->setNotes(null);
        $this->uuid = $uuid ?? $this->uuid ?? Digraph::uuid();
        $this->created = $created ?? $this->created ?? time();
        $this->created_by =  $created_by ?? $this->created_by ?? Session::uuid();
        $this->updated = $updated ?? $this->updated ?? time();
        $this->updated_by =  $updated_by ?? $this->updated_by ?? Session::uuid();
    }

    public function url(): URL
    {
        return $this->revision()->policy()->url($this->uuid());
    }

    public function setStart(DateTime $start)
    {
        $this->start = $start->format('Y-m-d');
        return $this;
    }

    public function setEnd(DateTime $end)
    {
        $this->end = $end->format('Y-m-d');
        return $this;
    }

    public function setName(string $name)
    {
        $this->title = $name;
        return $this;
    }

    public function setNotes(?RichContent $notes)
    {
        $notes = $notes ?? new RichContent('');
        $this->notes = json_encode($notes->array());
        return $this;
    }

    public function start(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d', $this->start);
    }

    public function end(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d', $this->end);
    }

    public function name(): string
    {
        return $this->title;
    }

    public function notes(): RichContent
    {
        return new RichContent(json_decode($this->notes, true));
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function revision(): PolicyRevision
    {
        return Revisions::get($this->revision_uuid);
    }

    public function revisionUUID(): string
    {
        return $this->revision_uuid;
    }

    public function createdBy(): User
    {
        return Users::user($this->created_by);
    }

    public function updatedBy(): User
    {
        return Users::user($this->updated_by);
    }

    public function createdByUUID(): ?string
    {
        return $this->created_by;
    }

    public function updatedByUUID(): ?string
    {
        return $this->updated_by;
    }

    public function created(): DateTime
    {
        return (new DateTime)->setTimestamp($this->created);
    }

    public function updated(): DateTime
    {
        return (new DateTime)->setTimestamp($this->updated);
    }

    public function insert()
    {
        return CommentPeriods::insert($this);
    }

    public function update()
    {
        return CommentPeriods::update($this);
    }

    public function delete()
    {
        return CommentPeriods::delete($this);
    }
}
