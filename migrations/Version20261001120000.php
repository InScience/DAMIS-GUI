<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converts the database and every table from utf8mb3 ("utf8") to utf8mb4.
 *
 * utf8mb3 cannot store 4-byte characters (emoji, some symbols and CJK characters): inserting one fails with
 * "Incorrect string value". utf8mb4_unicode_ci keeps the comparison and sorting rules of the previous
 * utf8mb3_unicode_ci. Only tables that are not yet utf8mb4_unicode_ci are converted, so the migration is safe
 * to run on any copy of the database.
 */
final class Version20261001120000 extends AbstractMigration
{
    private const CHARSET = 'utf8mb4';
    private const COLLATION = 'utf8mb4_unicode_ci';

    public function getDescription(): string
    {
        return 'Convert the database and all tables from utf8mb3 to utf8mb4 (utf8mb4_unicode_ci)';
    }

    // ALTER statements commit implicitly in MySQL, a surrounding transaction would only be misleading
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed on MySQL.'
        );

        $this->addSql(sprintf(
            'ALTER DATABASE %s CHARACTER SET %s COLLATE %s',
            $this->connection->quoteSingleIdentifier($this->connection->getDatabase()),
            self::CHARSET,
            self::COLLATION
        ));

        $tables = $this->connection->fetchFirstColumn(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND TABLE_COLLATION <> ?
             ORDER BY TABLE_NAME",
            [self::COLLATION]
        );

        foreach ($tables as $table) {
            $this->addSql(sprintf(
                'ALTER TABLE %s CONVERT TO CHARACTER SET %s COLLATE %s',
                $this->connection->quoteSingleIdentifier($table),
                self::CHARSET,
                self::COLLATION
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Converting back to utf8mb3 would destroy 4-byte characters stored since the upgrade; restore the backup taken before the migration instead.'
        );
    }
}
