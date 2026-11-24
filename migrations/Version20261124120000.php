<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Default 0.63 (the form default) for the RDF parameter "q" (resistance to noise, DamisService DF).
 *
 * The RDF form named the field "r" from 2015 to 2026-11, so "q" was never stored and every RDF run failed with
 * "empty value provided where a value is required in element 'q'". ExecuteExperimentCommand sends the default
 * when a parameter has no value, so the experiments saved in that time run without being edited.
 */
final class Version20261124120000 extends AbstractMigration
{
    private const WHERE = "ParameterSlug = 'q' AND ComponentID IN (SELECT ComponentID FROM component WHERE ComponentWSDLCallFunction = 'DF')";

    public function getDescription(): string
    {
        return 'Default value 0.63 for the RDF parameter q';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE parameter SET ParameterDefault = '0.63' WHERE ParameterDefault IS NULL AND ".self::WHERE);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE parameter SET ParameterDefault = NULL WHERE ParameterDefault = '0.63' AND ".self::WHERE);
    }
}
