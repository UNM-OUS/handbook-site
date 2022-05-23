<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Approvals;

use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\Session\Session;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class RevisionApproval
{
    protected $revision_uuid, $approver, $approved, $notes, $hidden;
    protected $uuid, $created, $created_by, $updated, $updated_by;

    public function __construct(
        string $revision_uuid,
        string $approver,
        DateTime $approved,
        ?string $notes,
        bool $hidden,
        string $uuid = null,
        int $created = null,
        string $created_by = null,
        int $updated = null,
        string $updated_by = null
    ) {
        $this->revision_uuid = $revision_uuid;
        $this->approver = $approver;
        $this->setApproved($approved);
        $this->notes = $notes ?? '';
        $this->hidden = $hidden;
        $this->uuid = $uuid ?? Digraph::uuid();
        $this->created = $created ?? time();
        $this->created_by =  $created_by ?? Session::uuid();
        $this->updated = $updated ?? time();
        $this->updated_by =  $updated_by ?? Session::uuid();
    }

    public function setHidden(bool $hidden)
    {
        $this->hidden = $hidden;
        return $this;
    }

    public function setApprover(string $approver)
    {
        $this->approver = $approver;
        return $this;
    }

    public function setApproved(DateTime $approved)
    {
        $approved = clone $approved;
        $approved->setTime(0, 0, 0, 0);
        $this->approved = $approved;
        return $this;
    }

    public function setNotes(?string $notes)
    {
        $this->notes = $notes ?? '';
        return $this;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function approver(): string
    {
        return $this->approver;
    }

    public function approved(): DateTime
    {
        return clone $this->approved;
    }

    public function notes(): string
    {
        return $this->notes ?? '';
    }

    public function hidden(): bool
    {
        return $this->hidden;
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
        return Approvals::insert($this);
    }

    public function update()
    {
        return Approvals::update($this);
    }

    public function delete()
    {
        return Approvals::delete($this);
    }
}
