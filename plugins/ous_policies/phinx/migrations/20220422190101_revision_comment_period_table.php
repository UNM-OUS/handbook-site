<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class RevisionCommentPeriodTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('ous_revision_comment')
            ->addColumn('uuid', 'uuid')
            ->addColumn('revision_uuid', 'uuid')
            ->addColumn('start', 'date')
            ->addColumn('end', 'date')
            ->addColumn('name', 'string', ['length' => 250])
            ->addColumn('notes', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR])
            ->addColumn('created', 'integer')
            ->addColumn('created_by', 'uuid')
            ->addColumn('updated', 'integer')
            ->addColumn('updated_by', 'uuid')
            ->addForeignKey(['revision_uuid'], 'ous_policy_revision', ['uuid'])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('start')
            ->addIndex('end')
            ->addIndex('created')
            ->addIndex('updated')
            ->create();
    }
}
