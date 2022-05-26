<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class PolicyRevisionTable extends AbstractMigration
{
    public function change()
    {
        $this->table('ous_policy_revision')
            ->addColumn('uuid', 'uuid')
            ->addColumn('page_uuid', 'uuid')
            ->addColumn('num', 'string', ['length' => 20, 'null' => true])
            ->addColumn('name', 'string', ['length' => 250])
            ->addColumn('title', 'string', ['length' => 250, 'null' => true])
            ->addColumn('effective', 'date', ['null' => true])
            ->addColumn('type', 'string', ['length' => 50])
            ->addColumn('moved', 'boolean')
            ->addColumn('state', 'string', ['length' => 20])
            ->addColumn('data', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR])
            ->addColumn('created', 'integer')
            ->addColumn('created_by', 'uuid')
            ->addColumn('updated', 'integer')
            ->addColumn('updated_by', 'uuid')
            ->addForeignKey(['page_uuid'], 'page', ['uuid'])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('num')
            ->addIndex('name')
            ->addIndex('effective')
            ->addIndex('type')
            ->addIndex('state')
            ->addIndex('created')
            ->addIndex('updated')
            ->create();
    }
}
