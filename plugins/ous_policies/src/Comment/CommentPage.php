<?php

namespace DigraphCMS_Plugins\unmous\ous_policies\Comment;

use DateTime;
use DigraphCMS\Content\Page;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\PolicyRevision;
use DigraphCMS_Plugins\unmous\ous_policies\Revisions\Revisions;

class CommentPage extends Page
{
    const DEFAULT_SLUG = 'comment_[uuid]';

    public function cronJob_frequent()
    {
        if ($this->isNow()) {
            $this['period_state'] = 'open';
            $this->setRevisionStates('comment');
        } elseif ($this->isOver()) {
            $this['period_state'] = 'over';
            $this->setRevisionStates('pending', 'comment');
        } else {
            $this['period_state'] = 'pending';
            $this->setRevisionStates('pending');
        }
    }

    public function permissions(URL $url, ?User $user = null): ?bool
    {
        if ($url->action() == 'copy') return false;
        else return parent::permissions($url, $user);
    }

    protected function setRevisionStates(string $state, array $onlyFrom = null)
    {
        foreach ($this->revisions() as $revision) {
            // don't do anything if state is already set
            if ($revision->state() == $state) continue;
            // don't do anything if there is an only-from condition and the current state is not $onlyFrom
            if ($onlyFrom && $revision->state() != $onlyFrom) continue;
            // set state and update
            $revision->setState($state)->update();
        }
    }

    public function routeClasses(): array
    {
        return ['policy-comment', 'page', '_any'];
    }

    public function parent(?URL $url = null): ?URL
    {
        if ($url) {
            if ($url->action() == 'index') return new URL('/under_review/');
        }
        return parent::parent($url);
    }

    public function insert(?string $parent_uuid = null)
    {
        $this->updateName();
        parent::insert($parent_uuid);
    }

    public function update()
    {
        $this->updateName();
        parent::update();
    }

    /**
     * @return PolicyRevision[]
     */
    public function revisions(): array
    {
        return array_filter(
            array_map(
                function ($uuid) {
                    return Revisions::get($uuid) ?? false;
                },
                $this['revisions'] ?? []
            )
        );
    }

    public function addRevision(PolicyRevision $revision)
    {
        $this['revisions'][] = $revision->uuid();
    }

    public function removeRevision(PolicyRevision $revision)
    {
        $updatedRevisions = array_filter(
            $this['revisions'],
            function ($uuid) use ($revision) {
                return $uuid != $revision->uuid();
            }
        );
        unset($this['revisions']);
        $this['revisions'] = $updatedRevisions;
    }

    /**
     * Called by insert and update, to ensure that name reflects current
     * first day and policy number list
     *
     * @return void
     */
    protected function updateName()
    {
        $to = implode(
            ', ',
            array_filter(array_map(
                function (PolicyRevision $revision) {
                    return $revision->number() ?? false;
                },
                $this->revisions()
            ))
        );
        $to = $to ? " to $to" : "";
        $this->name(
            sprintf(
                '%s: Proposed changes%s',
                $this->firstDay()->format('Y-m-d'),
                $to
            )
        );
    }

    public function isNow(): bool
    {
        $now = date('Y-m-d');
        return $now >= $this->firstDay()->format('Y-m-d')
            && $now <= $this->lastDay()->format('Y-m-d');
    }

    public function isOver(): bool
    {
        return date('Y-m-d') > $this->lastDay()->format('Y-m-d');
    }

    public function firstDay(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d', $this['first_day']);
    }

    public function lastDay(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d', $this['last_day']);
    }

    /**
     * Set the day this comment period _. If this differs from the previously
     * set value, period_state will be reset and automatic state-setting actions
     * will once again be able to change the state of the associated revisions.
     * 
     * @param DateTime $date
     * @return $this
     */
    public function setFirstDay(DateTime $date)
    {
        $date = $date->format('Y-m-d');
        if ($date != $this['first_day']) $this['period_state'] = false;
        $this['first_day'] = $date;
        return $this;
    }

    /**
     * Set the day this comment period _. If this differs from the previously
     * set value, period_state will be reset and automatic state-setting actions
     * will once again be able to change the state of the associated revisions.
     * 
     * @param DateTime $date
     * @return $this
     */
    public function setLastDay(DateTime $date)
    {
        $date = $date->format('Y-m-d');
        if ($date != $this['last_day']) $this['period_state'] = false;
        $this['last_day'] = $date;
        return $this;
    }
}
