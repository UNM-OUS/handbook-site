<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Revisions;

class RevisionType
{
    const TYPES = [
        'normal' => 'Normal revision',
        'minor' => 'Minor/Maintenance revision',
        'abolished' => 'Policy abolished',
        'created' => 'New policy',
        'firstweb' => 'First web version',
    ];

    protected $type;

    public function __construct(string $type = null)
    {
        $this->type = $type ?? 'normal';
    }

    /**
     * Set type
     *
     * @return $this
     */
    public function toNormal()
    {
        $this->type = 'normal';
        return $this;
    }

    /**
     * Set type
     *
     * @return $this
     */
    public function toMinor()
    {
        $this->type = 'normal';
        return $this;
    }

    /**
     * Set type
     *
     * @return $this
     */
    public function toMoved()
    {
        $this->type = 'normal';
        return $this;
    }

    /**
     * Set type
     *
     * @return $this
     */
    public function toAbolished()
    {
        $this->type = 'normal';
        return $this;
    }

    /**
     * Set type
     *
     * @return $this
     */
    public function toCreated()
    {
        $this->type = 'created';
        return $this;
    }

    public function label(): string
    {
        return static::TYPES[$this->type];
    }

    public function __toString()
    {
        return $this->type;
    }
}
