<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-user-roles',
    description: 'Converts user roles from serialized PHP to JSON format.',
)]
class FixUserRolesCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        $io->title('Starting user roles conversion...');

        // Fetch all users with their raw roles data
        $users = $connection->fetchAllAssociative('SELECT id, roles FROM users');

        $convertedCount = 0;
        foreach ($users as $user) {
            $id = $user['id'];
            $rolesRaw = $user['roles'];

            // Check if the data is a serialized string
            if ($this->isSerialized($rolesRaw)) {
                // Unserialize the data. Use error suppression for safety.
                $rolesArray = @unserialize($rolesRaw);

                if ($rolesArray === false) {
                    $io->warning(sprintf('Could not unserialize roles for user ID %d. Skipping.', $id));
                    continue;
                }

                // Re-encode as JSON
                $rolesJson = json_encode($rolesArray);

                // Update the raw value in the database
                $connection->executeStatement(
                    'UPDATE users SET roles = :roles WHERE id = :id',
                    ['roles' => $rolesJson, 'id' => $id]
                );
                $convertedCount++;
            }
        }

        if ($convertedCount > 0) {
            $io->success(sprintf('Successfully converted the roles for %d users.', $convertedCount));
        } else {
            $io->note('No users needed conversion. The data is likely already in JSON format.');
        }

        return Command::SUCCESS;
    }

    private function isSerialized($data): bool
    {
        // If it isn't a string, it isn't serialized
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        // A serialized string in PHP starts with a letter followed by a colon (e.g., a:, s:, i:)
        return (bool) preg_match('/^[a-zA-Z]:[0-9]+:/', $data);
    }
}