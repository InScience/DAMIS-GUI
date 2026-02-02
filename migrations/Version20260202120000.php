<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Merge clusters: remove MIF VU SK2 and rename MII Cluster to Server
 */
final class Version20260202120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Merge clusters: remove MIF VU SK2 (ID=2) and rename MII Cluster (ID=1) to Server';
    }

    public function up(Schema $schema): void
    {
        // Move all components from cluster 2 to cluster 1
        $this->addSql('UPDATE component SET ClusterID = 1 WHERE ClusterID = 2');

        // Update cluster 1 with new name and description
        $this->addSql("UPDATE cluster SET ClusterName = 'Server', ClusterDescription = 'Calculations are performed on the damis.midas.lt server' WHERE ClusterID = 1");

        // Delete cluster 2
        $this->addSql('DELETE FROM cluster WHERE ClusterID = 2');
    }

    public function down(Schema $schema): void
    {
        // Restore cluster 2
        $this->addSql("INSERT INTO cluster (ClusterID, ClusterName, ClusterWorkloadHost, ClusterDescription, ClusterUrl, WorkloadUrl) VALUES (2, 'MIF VU SK2', 'test', 'Supercomputer of Vilnius University Faculty of Mathematics and Informatics', 'http://mif.vu.lt/cluster/', 'http://k007.mif.vu.lt/ganglia2/')");

        // Restore cluster 1 original values
        $this->addSql("UPDATE cluster SET ClusterName = 'MII Cluster', ClusterDescription = 'Distributed Computing cluster of Vilnius University Institute of Mathematics and Informatics' WHERE ClusterID = 1");
    }
}
