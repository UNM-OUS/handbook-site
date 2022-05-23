<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Comment;

use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Session\Session;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class RevisionComment
{
    protected $revision_uuid, $start, $end, $name, $notes;
    protected $uuid, $created, $created_by, $updated, $updated_by;

    public function __construct(
        string $revision_uuid,
        DateTime $start,
        DateTime $end,
        string $name,
        ?RichContent $notes,
        string $uuid = null,
        int $created = null,
        string $created_by = null,
        int $updated = null,
        string $updated_by = null
    ) {
        $this->revision_uuid = $revision_uuid;
        $this->setStart($start);
        $this->setEnd($end);
        $this->name = $name;
        $this->setNotes($notes);
        $this->uuid = $uuid ?? Digraph::uuid();
        $this->created = $created ?? time();
        $this->created_by =  $created_by ?? Session::uuid();
        $this->updated = $updated ?? time();
        $this->updated_by =  $updated_by ?? Session::uuid();
    }

    public function setStart(DateTime $start)
    {
        $start = clone $start;
        $start->setTime(0, 0, 0, 0);
        $this->start = $start;
        return $this;
    }

    public function setEnd(DateTime $end)
    {
        $end = clone $end;
        $end->setTime(0, 0, 0, 0);
        $this->end = $end;
        return $this;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function setNotes(?RichContent $notes)
    {
        $this->notes = $notes ?? new RichContent('');
        return $this;
    }

    public function start(): DateTime
    {
        return clone $this->start;
    }

    public function end(): DateTime
    {
        return clone $this->end;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function notes(): RichContent
    {
        return $this->notes;
    }

    public function uuid(): string
    {
        return $this->uuid;
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
