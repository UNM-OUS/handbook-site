<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class GeneratedPdfTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('generated_policy_pdf')
            ->addColumn('uuid', 'uuid')
            ->addColumn('page_uuid', 'uuid')
            ->addColumn('date_year', 'integer')
            ->addColumn('date_month', 'integer')
            ->addColumn('date_day', 'integer')
            ->addColumn('filename', 'string', ['length' => 250])
            ->addColumn('created', 'integer')
            ->addColumn('data', 'blob')
            ->addForeignKey(['page_uuid'], 'page', ['uuid'])
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('date_year')
            ->addIndex('date_month')
            ->addIndex('date_day')
            ->addIndex('created')
            ->create();
    }
}
