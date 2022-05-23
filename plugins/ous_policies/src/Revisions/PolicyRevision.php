<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Revisions;

use DateTime;
use DigraphCMS\Content\Pages;
use DigraphCMS\Digraph;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\ous_policies\PolicyPage;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;
use Flatrr\FlatArray;

class PolicyRevision extends FlatArray
{
    protected $uuid;
    protected $page_uuid;
    protected $title;
    protected $num;
    protected $name;
    protected $effective;
    protected $type;
    protected $moved;
    protected $state;
    protected $data;
    protected $created;
    protected $created_by;
    protected $updated;
    protected $updated_by;

    public function __construct(
        string $title,
        string $page_uuid,
        ?string $num,
        string $name,
        ?DateTime $effective,
        string $type,
        bool $moved,
        string $state,
        array $data,
        string $uuid = null,
        int $created = null,
        ?string $created_by = null,
        int $updated = null,
        ?string $updated_by = null
    ) {
        $this->title = $title;
        $this->uuid = $uuid ?? Digraph::uuid('polrev');
        $this->page_uuid = $page_uuid;
        $this->num = $num;
        $this->name = $name;
        $this->effective = $effective;
        $this->type = $type;
        $this->moved = $moved;
        $this->state = $state;
        $this->created = $created ?? time();
        $this->created_by = $created_by ?? Session::uuid();
        $this->updated = $updated ?? time();
        $this->updated_by = $updated_by ?? Session::uuid();
        $this->set(null, $data);
    }

    /**
     * Get the next revision after this one. Always returns null if no effective
     * date exists for this revision.
     *
     * @return PolicyRevision|null
     */
    public function nextRevision(): ?PolicyRevision
    {
        if (!$this->effective()) return null;
        return Revisions::select($this->pageUUID())
            ->publicView()
            ->where('uuid <> ?', [$this->uuid()])
            ->where('effective >= ?', $this->effective()->format('Y-m-d'))
            ->order('effective ASC, id ASC')
            ->fetch();
    }

    /**
     * Get the previous revision to this one. Returns the policy's current revision
     * if this revision has no effective date.
     *
     * @return PolicyRevision|null
     */
    public function previousRevision(): ?PolicyRevision
    {
        if (!$this->effective()) return $this->policy()->currentRevision();
        return Revisions::select($this->pageUUID())
            ->publicView()
            ->where('uuid <> ?', [$this->uuid()])
            ->where('effective IS NOT NULL')
            ->where('effective <= ?', $this->effective()->format('Y-m-d'))
            ->order('effective DESC, id DESC')
            ->limit(1)
            ->fetch();
    }

    public function notes(): ?RichContent
    {
        return $this['notes']
            ? new RichContent($this['notes'])
            : null;
    }

    public function setNotes(RichContent $content)
    {
        unset($this['notes']);
        $this['notes'] = $content->array();
        return $this;
    }

    public function body(): RichContent
    {
        return new RichContent($this['body']);
    }

    public function url(): URL
    {
        $url = Pages::get($this->page_uuid)->url($this->uuid());
        $url->setName($this->metaTitle());
        return $url;
    }

    public function metaTitle(): string
    {
        if ($this->effective) {
            return sprintf(
                '%s - %s',
                $this->title(),
                Format::date($this->effective(), false, false)
            );
        } else {
            return $this->title();
        }
    }

    public function insert()
    {
        return Revisions::insert($this);
    }

    public function update()
    {
        return Revisions::update($this);
    }

    public function delete()
    {
        // do main deletion process
        return Revisions::delete($this);
    }

    /**
     * Set UUID
     *
     * @param string $uuid
     * @return $this
     */
    public function setUUID(string $uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * Set page UUID
     *
     * @param string $uuid
     * @return $this
     */
    public function setPageUUID(string $page_uuid)
    {
        $this->page_uuid = $page_uuid;
        return $this;
    }

    /**
     * Set revision title
     *
     * @param string|null $title
     * @return $this
     */
    public function setTitle(?string $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set policy number
     *
     * @param string|null $number
     * @return $this
     */
    public function setNumber(?string $number)
    {
        $this->num = $number;
        return $this;
    }

    /**
     * Set policy name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the effective date of this revision
     *
     * @param DateTime $date
     * @return $this
     */
    public function setEffective($date)
    {
        $this->effective = $date;
        return $this;
    }

    /**
     * Set revision state
     *
     * @param string $state
     * @return $this
     */
    public function setState(string $state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Set revision type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set whether revision is a move/rename
     *
     * @param bool $moved
     * @return $this
     */
    public function setMoved(bool $moved)
    {
        $this->moved = $moved;
        return $this;
    }

    /**
     * Set policy revision body
     *
     * @param RichContent $body
     * @return $this
     */
    public function setBody(RichContent $body)
    {
        $this['body'] = $body->array();
        return $this;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function pageUUID(): string
    {
        return $this->page_uuid;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function policyUUID(): string
    {
        return $this->page_uuid;
    }

    public function policy(): PolicyPage
    {
        return Pages::get($this->page_uuid);
    }

    public function number(): ?string
    {
        return $this->num;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function fullName(): string
    {
        $name = $this->number();
        if ($name) $name = "$name: ";
        return $name . $this->name();
    }

    public function effective(): ?DateTime
    {
        if (!$this->effective) return null;
        $this->effective->setTime(0, 0, 0, 0);
        return clone $this->effective;
    }

    public function type(): RevisionType
    {
        return new RevisionType($this->type);
    }

    public function moved(): bool
    {
        return $this->moved;
    }

    public function state(): RevisionState
    {
        // state can't be certain things if there's no effective date
        if (!$this->effective()) {
            // published becomes pending
            if ($this->state == 'published') $this->state = 'pending';
        }
        return new RevisionState($this->state);
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
}
