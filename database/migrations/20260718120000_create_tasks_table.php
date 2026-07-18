<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTasksTable extends AbstractMigration
{
    // change() se aplica al migrar y Phinx sabe revertirla automáticamente.
    public function change(): void
    {
        // Phinx añade por defecto una columna 'id' (PK, autoincremental).
        $this->table('tasks')
            ->addColumn('title', 'string', ['limit' => 255])
            ->addColumn('completed', 'boolean', ['default' => false])
            ->addTimestamps()
            ->create();
    }
}
