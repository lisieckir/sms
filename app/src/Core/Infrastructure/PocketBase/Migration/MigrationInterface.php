<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\PocketBase\Migration;

interface MigrationInterface
{
    public function up(): void;

    public function down(): void;

    public function getDescription(): string;

    public function getVersion(): string;
}
