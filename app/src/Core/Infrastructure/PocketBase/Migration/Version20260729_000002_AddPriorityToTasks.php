<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\PocketBase\Migration;

final class Version20260729_000002_AddPriorityToTasks extends AbstractMigration
{
    public function up(): void
    {
        $this->addField('tasks', 'priority', 'text');
    }

    public function down(): void
    {
        $this->removeField('tasks', 'priority');
    }

    public function getDescription(): string
    {
        return 'Add priority field to tasks collection';
    }
}
