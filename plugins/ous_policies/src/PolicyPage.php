<?php

namespace DigraphCMS_Plugins\unmous\ous_policies;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DB;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Search\Search;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\RevisionSelect;

class PolicyPage extends AbstractPage
{
    const DEFAULT_SLUG = null;
    const DEFAULT_UNIQUE_SLUG = false;

    protected $currentRevision = false;

    public function cronJob_index_pages()
    {
        // index page
        $body = $this->richContent('body');
        if ($body) Search::indexURL($this->uuid(), $this->url(), $this->name(), $body->html());
        // index revisions
        $revisions = $this->revisions()->publicView();
        if ($revisions->count() <= 1) return; // only index revisions if there's more than one
        while ($revision = $revisions->fetch()) {
            $title = $revision->number();
            if (!$title || $title == 'Information') {
                $title = $revision->name();
            }
            $title .= ': ' . $revision->title();
            if ($revision->effective()) {
                $title .= ': ' . Format::date($revision->effective(), true, true);
            }
            Search::indexURL($this->uuid(), $revision->url(), $title, implode(' ', [
                $revision->effective() ? Format::date($revision->effective(), true, true) : '',
                $revision->number(),
                $revision->name(),
                $revision->title(),
                $revision->notes()->html()
            ]));
        }
    }

    /**
     * PolicyPage overrides settings to make all non-index URLs use UUIDs
     *
     * @param string|null $action
     * @param array|null $args
     * @param boolean|null $uuid
     * @return URL
     */
    public function url(?string $action = null, ?array $args = null, ?bool $uuid = null): URL
    {
        return parent::url(
            $action,
            $args,
            (!$action || $action == 'index' ? $uuid : true)
        );
    }

    /**
     * Need to extend recursive deletion to delete all revisions for a page
     *
     * @param DeferredJob $job
     * @param PolicyPage $page
     * @return void
     */
    public static function onRecursiveDelete(DeferredJob $job, AbstractPage $page)
    {
        // delete revisions
        $revisions = Revisions::select($page->uuid());
        while ($revision = $revisions->fetch()) {
            $uuid = $revision->uuid();
            $job->spawn(function () use ($uuid) {
                $revision = Revisions::get($uuid);
                if (!$revision) return "Revision $uuid already deleted";
                $revision->delete();
                return "Deleted revision $uuid";
            });
        }
        // delete search indexes
        $uuid = $page->uuid();
        $job->spawn(function () use ($uuid) {
            $n = DB::query()
                ->delete('search_index')
                ->where('owner = ?', [$uuid])
                ->execute();
            return "Deleted search indexes created by page $uuid ($n)";
        });
    }


    /**
     * Check regularly to ensure policy name and number match current revision.
     *
     * @return void
     */
    public function cronJob_halfhourly()
    {
        $changed = false;
        if ($current = $this->currentRevision()) {
            if ($this['current'] != static::revisionCacheArray($current)) {
                $this['current'] = static::revisionCacheArray($current);
                $changed = true;
            }
        } elseif ($this['current']) {
            unset($this['current']);
            $changed = true;
        }
        if ($current && $prev = $this->currentRevision()->previousRevision()) {
            if ($this['previous'] != static::revisionCacheArray($prev)) {
                $this['previous'] = static::revisionCacheArray($prev);
                $changed = true;
            }
        } elseif ($this['previous']) {
            unset($this['previous']);
            $changed = true;
        }
        if ($changed) $this->update();
    }

    protected static function revisionCacheArray(PolicyRevision $rev): array
    {
        return [
            'uuid' => $rev->uuid(),
            'name' => $rev->name(),
            'number' => $rev->number(),
            'state' => $rev->state()->__toString(),
            'type' => $rev->type()->__toString(),
            'effective' => $rev->effective()->getTimestamp()
        ];
    }

    public function revisions(): RevisionSelect
    {
        return Revisions::select($this->uuid())
            ->publicView();
    }

    public function pastRevisions(): RevisionSelect
    {
        $query = $this->revisions()
            ->where('effective <= ?', [time()]);
        if ($this->currentRevision()) {
            $query->where('uuid <> ?', [$this->currentRevision()->uuid()]);
        }
        return $query;
    }

    public function futureRevisions(): RevisionSelect
    {
        return $this->revisions()
            ->where('effective > ? OR effective is null', [time()]);
    }

    public function currentRevision(): ?PolicyRevision
    {
        if ($this->currentRevision === false) {
            $this->currentRevision = Revisions::select($this->uuid())
                ->where('effective IS NOT NULL')
                ->where('effective <= ?', [date('Y-m-d')])
                ->where('state = "published"')
                ->order('effective DESC, created DESC')
                ->limit(1)
                ->fetch();
        }
        return $this->currentRevision;
    }

    public function richContent(string $index, ?RichContent $content = null): RichContent
    {
        // try to pull body from current revision
        if ($this->currentRevision()) {
            return $this->currentRevision()->body();
        }
        // fall back on parent tools for non-body rich content
        if ($index == 'body') {
            return new RichContent("<div class='notification notification--notice'>No content found for " . $this->name() . ", please check back later.</div>");
        } else {
            return parent::richContent($index, $content);
        }
    }

    public function allRichContent(): array
    {
        return [];
    }

    public function name(?string $name = null, bool $unfiltered = false, bool $forDB = false): string
    {
        $number = $this->policyNumber();
        $name = $this->policyName();
        if ($number) return "$number: $name";
        else return $name;
    }

    public function policyName(): ?string
    {
        return $this['current'] ? $this['current.name'] : $this['policy_name'];
    }

    public function policyNumber(): ?string
    {
        return $this['current'] ? $this['current.number'] : $this['policy_number'];
    }

    public function setPolicyNumber(?string $policyNumber)
    {
        unset($this['policy_number']);
        if ($policyNumber) $this['policy_number'] = $policyNumber;
        return $this;
    }

    public function setPolicyName(?string $policyName)
    {
        unset($this['policy_name']);
        if ($policyName) $this['policy_name'] = $policyName;
        return $this;
    }

    public function slugVariable(string $name): ?string
    {
        switch ($name) {
            case 'policy-slug':
                if (preg_match('/^[a-z][0-9]/i', $this->policyNumber() ?? '')) {
                    return '/' . strtolower($this->policyNumber());
                } else {
                    return $this->name(null, true, true);
                }
            default:
                return parent::slugVariable($name);
        }
    }

    public function slugPattern(?string $slugPattern = null): ?string
    {
        if ($slugPattern !== null) {
            parent::slugPattern($slugPattern);
        }
        if (parent::slugPattern()) {
            return parent::slugPattern();
        } else {
            return '[policy-slug]';
        }
    }

    public function permissions(URL $url, ?User $user = null): ?bool
    {
        if ($url->action() == 'copy') return false;
        if ($url->action() == '_revision_history') return true;
        if ($url->actionPrefix() == 'polrev') return true;
        return parent::permissions($url, $user);
    }

    public function routeClasses(): array
    {
        return ['policy', '_any'];
    }
}
