<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Console;

use App\IdentityAccess\Application\Command\RegisterUser\RegisterUserCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'add:user', description: 'Create a new user and output the initial password')]
final class AddUserCommand extends Command
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Role (user or admin)', 'user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $role = $input->getOption('role');

        if (!\str_contains($email, '@')) {
            $output->writeln('<error>Invalid email address.</error>');
            return Command::FAILURE;
        }

        $localPart = explode('@', $email)[0];
        $password = bin2hex(random_bytes(8));
        $username = $localPart;

        $this->commandBus->dispatch(new RegisterUserCommand(
            firstName: ucfirst($localPart),
            lastName: 'User',
            email: $email,
            username: $username,
            plainPassword: $password,
            isAdmin: $role === 'admin',
        ));

        $output->writeln(sprintf('User <info>%s</info> created successfully.', $email));
        $output->writeln(sprintf('Initial password: <comment>%s</comment>', $password));

        return Command::SUCCESS;
    }
}
