<?php

declare(strict_types=1);

namespace App\IdentityAccess\Infrastructure\Console;

use App\IdentityAccess\Application\Command\RegisterUser\RegisterUserCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:create-admin-user', description: 'Creates the initial admin user')]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email')
            ->addArgument('username')
            ->addArgument('password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email') ?? 'admin@sidegigs.local';
        $username = $input->getArgument('username') ?? 'admin';
        $password = $input->getArgument('password');
        if (!$password) {
            $helper = $this->getHelper('question');
            $question = new Question('Password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $question);
        }

        $this->commandBus->dispatch(new RegisterUserCommand(
            firstName: 'Admin',
            lastName: 'User',
            email: $email,
            username: $username,
            plainPassword: $password,
            isAdmin: true,
        ));

        $output->writeln(sprintf('Admin user <info>%s</info> created successfully.', $email));
        return Command::SUCCESS;
    }
}
