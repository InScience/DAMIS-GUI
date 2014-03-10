<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cluster
 *
 * @ORM\Table(name="cluster")
 * @ORM\Entity
 */
class Cluster
{
    /**
     * @var string
     *
     * @ORM\Column(name="ClusterName", type="string", length=80, nullable=false)
     */
    private $clustername;

    /**
     * @var string
     *
     * @ORM\Column(name="ClusterWorkloadHost", type="string", length=255, nullable=false)
     */
    private $clusterworkloadhost;

    /**
     * @var string
     *
     * @ORM\Column(name="ClusterDescription", type="string", length=500, nullable=true)
     */
    private $clusterdescription;

    /**
     * @var integer
     *
     * @ORM\Column(name="ClusterID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $clusterid;



    /**
     * Set clustername
     *
     * @param string $clustername
     * @return Cluster
     */
    public function setClustername($clustername)
    {
        $this->clustername = $clustername;

        return $this;
    }

    /**
     * Get clustername
     *
     * @return string 
     */
    public function getClustername()
    {
        return $this->clustername;
    }

    /**
     * Set clusterworkloadhost
     *
     * @param string $clusterworkloadhost
     * @return Cluster
     */
    public function setClusterworkloadhost($clusterworkloadhost)
    {
        $this->clusterworkloadhost = $clusterworkloadhost;

        return $this;
    }

    /**
     * Get clusterworkloadhost
     *
     * @return string 
     */
    public function getClusterworkloadhost()
    {
        return $this->clusterworkloadhost;
    }

    /**
     * Set clusterdescription
     *
     * @param string $clusterdescription
     * @return Cluster
     */
    public function setClusterdescription($clusterdescription)
    {
        $this->clusterdescription = $clusterdescription;

        return $this;
    }

    /**
     * Get clusterdescription
     *
     * @return string 
     */
    public function getClusterdescription()
    {
        return $this->clusterdescription;
    }

    /**
     * Get clusterid
     *
     * @return integer 
     */
    public function getClusterid()
    {
        return $this->clusterid;
    }
}
