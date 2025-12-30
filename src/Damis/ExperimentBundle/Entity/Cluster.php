<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cluster
 */
#[ORM\Table(name: 'cluster')]
#[ORM\Entity]
class Cluster
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'ClusterName', type: 'string', length: 80, nullable: false)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ClusterWorkloadHost', type: 'string', length: 255, nullable: false)]
    private $workloadHost;

    /**
     * @var string
     */
    #[ORM\Column(name: 'WorkloadUrl', type: 'string', length: 255, nullable: true)]
    private $workloadUrl;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ClusterUrl', type: 'string', length: 255, nullable: true)]
    private $clusterUrl;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ClusterDescription', type: 'string', length: 500, nullable: true)]
    private $description;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'ClusterID', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;



    /**
     * Set name
     *
     * @param string $name
     * @return Cluster
     */
    public function setname($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getname()
    {
        return $this->name;
    }

    /**
     * Set workloadhost
     *
     * @param string $workloadHost
     * @return Cluster
     */
    public function setWorkloadHost($workloadHost)
    {
        $this->workloadHost = $workloadHost;

        return $this;
    }

    /**
     * Get workloadhost
     *
     * @return string
     */
    public function getWorkloadHost()
    {
        return $this->workloadHost;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Cluster
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $clusterUrl
     */
    public function setClusterUrl($clusterUrl)
    {
        $this->clusterUrl = $clusterUrl;
    }

    /**
     * @return string
     */
    public function getClusterUrl()
    {
        return $this->clusterUrl;
    }

    /**
     * @param string $workloadUrl
     */
    public function setWorkloadUrl($workloadUrl)
    {
        $this->workloadUrl = $workloadUrl;
    }

    /**
     * @return string
     */
    public function getWorkloadUrl()
    {
        return $this->workloadUrl;
    }
}
