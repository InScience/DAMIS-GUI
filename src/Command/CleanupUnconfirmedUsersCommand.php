<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:cleanup-unconfirmed-users',
    description: 'Deletes unconfirmed users (enabled=false) registered more than 7 days ago and all their related data.',
)]
class CleanupUnconfirmedUsersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private string $projectDir;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->projectDir = $params->get('kernel.project_dir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        $io->title('Cleaning up unconfirmed users...');

        $cutoffDate = new \DateTime('-3 days');

        // Find unconfirmed users (without ROLE_CONFIRMED) registered more than 3 days ago
        $users = $connection->fetchAllAssociative(
            'SELECT id, username FROM users WHERE roles NOT LIKE :role AND registeredAt < :cutoff',
            ['role' => '%ROLE_CONFIRMED%', 'cutoff' => $cutoffDate->format('Y-m-d H:i:s')]
        );

        if (empty($users)) {
            $io->success('No unconfirmed users to clean up.');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Found %d unconfirmed user(s) to delete.', count($users)));

        $deletedCount = 0;
        $publicDir = $this->projectDir . '/public';

        foreach ($users as $user) {
            $userId = $user['id'];
            $username = $user['username'];

            try {
                $connection->beginTransaction();

                // 1. Delete experiments (CASCADE handles: workflowtask -> parametervalue -> pvalueoutpvaluein)
                $experimentCount = $connection->executeStatement(
                    'DELETE FROM experiment WHERE UserID = :userId',
                    ['userId' => $userId]
                );

                // 2. Delete datasets + their physical files
                $datasets = $connection->fetchAllAssociative(
                    'SELECT DatasetID, DatasetFilePath FROM dataset WHERE UserID = :userId',
                    ['userId' => $userId]
                );
                foreach ($datasets as $dataset) {
                    if (!empty($dataset['DatasetFilePath'])) {
                        $filePath = $publicDir . $dataset['DatasetFilePath'];
                        if (is_file($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                $datasetCount = $connection->executeStatement(
                    'DELETE FROM dataset WHERE UserID = :userId',
                    ['userId' => $userId]
                );

                // 3. Delete user algorithm files + their physical files
                $algorithms = $connection->fetchAllAssociative(
                    'SELECT id, file_path FROM useralgorithm WHERE user_id = :userId',
                    ['userId' => $userId]
                );
                foreach ($algorithms as $algorithm) {
                    if (!empty($algorithm['file_path'])) {
                        $filePath = $publicDir . $algorithm['file_path'];
                        if (is_file($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                $algorithmCount = $connection->executeStatement(
                    'DELETE FROM useralgorithm WHERE user_id = :userId',
                    ['userId' => $userId]
                );

                // 4. Delete the user record
                $connection->executeStatement(
                    'DELETE FROM users WHERE id = :userId',
                    ['userId' => $userId]
                );

                $connection->commit();
                $deletedCount++;

                $io->text(sprintf(
                    '  Deleted user "%s" (ID: %d) — %d experiment(s), %d dataset(s), %d algorithm(s)',
                    $username, $userId, $experimentCount, $datasetCount, $algorithmCount
                ));
            } catch (\Exception $e) {
                $connection->rollBack();
                $io->error(sprintf('Failed to delete user "%s" (ID: %d): %s', $username, $userId, $e->getMessage()));
            }
        }

        $io->success(sprintf('Deleted %d unconfirmed user(s).', $deletedCount));

        return Command::SUCCESS;
    }
}
