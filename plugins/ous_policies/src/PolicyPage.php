<?php

namespace DigraphCMS_Plugins\unmous\ous_policies;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\RichContent\RichContent;
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

    public static function onRecursiveDelete(DeferredJob $job, PolicyPage $page)
    {
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
    }

    /**
     * Do a weekly refresh of cron jobs for every policy, just in case cron
     * tasks are added later.
     *
     * @return void
     */
    public function onCron_weekly()
    {
        $this->prepareCronJobs();
    }

    /**
     * Check regularly to ensure policy name and number match current revision.
     *
     * @return void
     */
    public function onCron_frequent()
    {
        if ($rev = $this->currentRevision()) {
            $this['current'] = static::revisionCacheArray($rev);
            if ($prev = $this->currentRevision()->previousRevision()) {
                $this['previous'] = static::revisionCacheArray($prev);
            } else {
                unset($this['previous']);
            }
        } else {
            unset($this['current']);
        }
        $this->update();
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
            ->where('effective > ?', [time()]);
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
            return new RichContent("<div class='notification notification--notice'>No content found, please check back later.</div>");
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
        $name = parent::name($name, $unfiltered);
        if (!$forDB && $number = $this->policyNumber()) {
            return "$number: $name";
        } else {
            return $name;
        }
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
        if ($url->action() == 'revision_history') return $this->revisions()->count() > 1;
        if (substr($url->action(), 0, 7) == 'polrev_') return true;
        return parent::permissions($url, $user);
    }

    public function routeClasses(): array
    {
        return ['policy', '_any'];
    }
}
