<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\PocketBase\Migration;

final class Version20260729_000001_AddSettlementTypeToClients extends AbstractMigration
{
    public function up(): void
    {
        $this->addField('clients', 'settlementType', 'text');
    }

    public function down(): void
    {
        $this->removeField('clients', 'settlementType');
    }

    public function getDescription(): string
    {
        return 'Add settlementType field to clients collection';
    }
}
