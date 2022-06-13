<?php

namespace DigraphCMS_Plugins\byjoby\glossary;

use DateTime;
use DigraphCMS\Content\Pages;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Session\Session;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Envms\FluentPDO\Queries\Select;

class GlossaryTerm
{
    protected $uuid;
    protected $page_uuid;
    protected $name;
    protected $link;
    protected $body;
    protected $created;
    protected $created_by;
    protected $updated;
    protected $updated_by;

    public function __construct(
        string $page_uuid,
        string $name,
        string $link = null,
        string $body,
        int $created = null,
        string $created_by = null,
        string $updated = null,
        string $updated_by = null,
        string $uuid = null
    ) {
        $this->name = $name;
        $this->uuid = $uuid ?? Digraph::uuid();
        $this->page_uuid = $page_uuid;
        $this->link = $link ? $link : null;
        $this->body = $body ?? '';
        $this->created = $created ?? time();
        $this->created_by = $created_by ?? Session::uuid();
        $this->updated = $updated ?? time();
        $this->updated_by = $updated_by ?? Session::uuid();
    }

    public function patterns(): Select
    {
        return DB::query()->from('glossary_pattern')
            ->where('glossary_term_uuid = ?', [$this->uuid()]);
    }

    public function pageUUID(): string
    {
        return $this->page_uuid;
    }

    public function page(): GlossaryPage
    {
        return Pages::get($this->pageUUID());
    }

    public function cardContent(): string
    {
        if ($this->link()) {
            return '<strong><a href="' . $this->link() . '" target="_blank">' . $this->name() . '</a></strong>' . new RichContent($this->body());
        } else {
            return '<strong>' . $this->name() . '</strong>' . new RichContent($this->body());
        }
    }

    /**
     * Set Page
     *
     * @param GlossaryPage $page
     * @return $this
     */
    public function setPage(GlossaryPage $page)
    {
        $this->page_uuid = $page->uuid();
        return $this;
    }

    /**
     * Add a pattern, overwriting if it already exists
     *
     * @param string $pattern
     * @param boolean $regex
     * @return $this
     */
    public function addPattern(string $pattern, bool $regex)
    {
        DB::beginTransaction();
        static::deletePattern($pattern);
        DB::query()->insertInto(
            'glossary_pattern',
            [
                'glossary_term_uuid' => $this->uuid(),
                'pattern' => $pattern,
                'regex' => $regex
            ]
        )->execute();
        DB::commit();
        return $this;
    }

    /**
     * Delete a pattern matching the given pattern
     *
     * @param string $pattern
     * @return $this
     */
    public function deletePattern(string $pattern)
    {
        DB::query()->delete('glossary_pattern')
            ->where('glossary_term_uuid = ?', [$this->uuid()])
            ->where('pattern = ?', [$pattern])
            ->execute();
        return $this;
    }

    public function delete()
    {
        DB::beginTransaction();
        DB::query()->delete('glossary_pattern')
            ->where('glossary_term_uuid = ?', [$this->uuid()])
            ->execute();
        DB::query()->delete('glossary_term')
            ->where('uuid = ?', [$this->uuid()])
            ->execute();
        DB::commit();
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * Set display name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    /**
     * Set URL
     *
     * @param string $uuid
     * @return $this
     */
    public function setUUID(string $uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function link(): ?string
    {
        return $this->link;
    }

    /**
     * Set link URL
     *
     * @param string|null $link
     * @return $this
     */
    public function setLink(string $link = null)
    {
        $this->link = $link ? $link : null;
        return $this;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * Set body text
     *
     * @param string $body
     * @return $this
     */
    public function setBody(string $body)
    {
        $this->body = $body;
        return $this;
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
