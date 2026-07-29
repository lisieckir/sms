<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:pocketbase:generate-migration', description: 'Generate a new PocketBase migration class')]
final class GenerateMigrationCommand extends Command
{
    public function __construct(
        private string $migrationDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('description', InputArgument::REQUIRED, 'Short description of the migration (e.g. AddFieldToClients)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $description = $input->getArgument('description');

        $now = new \DateTimeImmutable();
        $version = $now->format('Ymd_His');
        $className = 'Version' . $now->format('Ymd') . '_' . $now->format('His') . '_' . $description;
        $filePath = $this->migrationDir . '/' . $className . '.php';

        if (file_exists($filePath)) {
            $io->error("Migration $className already exists");
            return Command::FAILURE;
        }

        $namespace = 'App\\Core\\Infrastructure\\PocketBase\\Migration';

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace $namespace;

final class $className extends AbstractMigration
{
    public function up(): void
    {
        // Schema
        // \$this->addField('collection', 'fieldName', 'text');
        // \$this->removeField('collection', 'fieldName');

        // Records
        // \$this->createRecord('collection', ['field' => 'value']);
        // \$this->updateRecord('collection', 'field="value"', ['field' => 'new']);
        // \$this->upsertRecord('collection', 'field="value"', ['field' => 'value']);
        // \$this->deleteRecord('collection', 'field="value"');
        // \$items = \$this->fetchRecords('collection', ['filter' => '...']);

        // Raw API
        // \$result = \$this->api('GET', '/collections/...');
    }

    public function down(): void
    {
        // Revert what up() did
    }

    public function getDescription(): string
    {
        return '$description';
    }
}

PHP;

        file_put_contents($filePath, $content);
        $io->success("Created $filePath");

        return Command::SUCCESS;
    }
}
