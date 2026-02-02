<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove duplicate components that existed for the second cluster
 */
final class Version20260202130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove duplicate components (originally for cluster 2) - keep only one of each component';
    }

    public function up(Schema $schema): void
    {
        // The duplicate components have even IDs (2, 4, 6, 8, etc.)
        // They were originally for cluster 2, now merged to cluster 1
        // We need to delete their parameters first, then the components

        // Delete parameters for duplicate components (even IDs from 2 to 46)
        $this->addSql('DELETE FROM parameter WHERE ComponentID IN (2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40, 42, 44, 46)');

        // Delete duplicate components
        $this->addSql('DELETE FROM component WHERE ComponentID IN (2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40, 42, 44, 46)');
    }

    public function down(Schema $schema): void
    {
        // This migration is not easily reversible - would need to restore from data.sql backup
        $this->throwIrreversibleMigrationException('Cannot reverse component deletion. Restore from backup if needed.');
    }
}
