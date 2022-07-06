<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Revisions;

class RevisionState
{
    const STATES = [
        'draft' => 'Draft',
        'comment' => 'Out for comment',
        'pending' => 'Action or approval pending',
        'published' => 'Published',
        'cancelled' => 'Cancelled',
        'hidden' => 'Hidden'
    ];

    protected $state;

    public function __construct(string $state = null)
    {
        $this->state = $state ?? 'draft';
    }

    /**
     * Set state
     *
     * @return $this
     */
    public function toDraft()
    {
        $this->state = 'draft';
        return $this;
    }

    /**
     * Set state
     *
     * @return $this
     */
    public function toComment()
    {
        $this->state = 'comment';
        return $this;
    }

    /**
     * Set state
     *
     * @return $this
     */
    public function toPublished()
    {
        $this->state = 'published';
        return $this;
    }

    public function label(): string
    {
        return static::STATES[$this->state];
    }

    public function __toString()
    {
        return $this->state;
    }
}
