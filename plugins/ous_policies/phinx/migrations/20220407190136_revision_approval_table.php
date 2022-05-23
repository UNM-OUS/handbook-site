<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class RevisionApprovalTable extends AbstractMigration
{
    public function change()
    {
        $this->table('ous_revision_approval')
            ->addColumn('uuid', 'uuid')
            ->addColumn('revision_uuid', 'uuid')
            ->addColumn('approver', 'string', ['length' => 250])
            ->addColumn('approved', 'date')
            ->addColumn('notes', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR])
            ->addColumn('hidden', 'boolean')
            ->addColumn('created', 'integer')
            ->addColumn('created_by', 'uuid')
            ->addColumn('updated', 'integer')
            ->addColumn('updated_by', 'uuid')
            ->addForeignKey(['revision_uuid'], 'ous_policy_revision', ['uuid'])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('approver')
            ->addIndex('approved')
            ->addIndex('hidden')
            ->addIndex('created')
            ->addIndex('updated')
            ->create();
    }
}
