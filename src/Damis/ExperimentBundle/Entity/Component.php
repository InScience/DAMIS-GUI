<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Component
 *
 * @ORM\Table(name="component", uniqueConstraints={@ORM\UniqueConstraint(name="COMPONENT_PK", columns={"ComponentID"})}, indexes={@ORM\Index(name="FK_COMPONENT_CLUSTER", columns={"ClusterID"}), @ORM\Index(name="FK_COMPONENT_COMPONENTTYPE", columns={"ComponentTypeID"})})
 * @ORM\Entity
 */
class Component
{
    /**
     * @var string
     *
     * @ORM\Column(name="ComponentName", type="string", length=80, nullable=false)
     */
    private $componentname;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentIcon", type="string", length=255, nullable=false)
     */
    private $componenticon;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentWSDLRunHost", type="string", length=255, nullable=false)
     */
    private $componentwsdlrunhost;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentWSDLCallFunction", type="string", length=80, nullable=false)
     */
    private $componentwsdlcallfunction;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentDescription", type="string", length=500, nullable=true)
     */
    private $componentdescription;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentAltDescription", type="string", length=80, nullable=true)
     */
    private $componentaltdescription;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentLabelLT", type="string", length=255, nullable=true)
     */
    private $componentlabellt;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentLabelEN", type="string", length=255, nullable=true)
     */
    private $componentlabelen;

    /**
     * @var integer
     *
     * @ORM\Column(name="ComponentID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $componentid;

    /**
     * @var \Damis\ExperimentBundle\Entity\Cluster
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\Cluster")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ClusterID", referencedColumnName="ClusterID")
     * })
     */
    private $clusterid;

    /**
     * @var \Damis\ExperimentBundle\Entity\Componenttype
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\Componenttype")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ComponentTypeID", referencedColumnName="ComponentTypeID")
     * })
     */
    private $componenttypeid;



    /**
     * Set componentname
     *
     * @param string $componentname
     * @return Component
     */
    public function setComponentname($componentname)
    {
        $this->componentname = $componentname;

        return $this;
    }

    /**
     * Get componentname
     *
     * @return string 
     */
    public function getComponentname()
    {
        return $this->componentname;
    }

    /**
     * Set componenticon
     *
     * @param string $componenticon
     * @return Component
     */
    public function setComponenticon($componenticon)
    {
        $this->componenticon = $componenticon;

        return $this;
    }

    /**
     * Get componenticon
     *
     * @return string 
     */
    public function getComponenticon()
    {
        return $this->componenticon;
    }

    /**
     * Set componentwsdlrunhost
     *
     * @param string $componentwsdlrunhost
     * @return Component
     */
    public function setComponentwsdlrunhost($componentwsdlrunhost)
    {
        $this->componentwsdlrunhost = $componentwsdlrunhost;

        return $this;
    }

    /**
     * Get componentwsdlrunhost
     *
     * @return string 
     */
    public function getComponentwsdlrunhost()
    {
        return $this->componentwsdlrunhost;
    }

    /**
     * Set componentwsdlcallfunction
     *
     * @param string $componentwsdlcallfunction
     * @return Component
     */
    public function setComponentwsdlcallfunction($componentwsdlcallfunction)
    {
        $this->componentwsdlcallfunction = $componentwsdlcallfunction;

        return $this;
    }

    /**
     * Get componentwsdlcallfunction
     *
     * @return string 
     */
    public function getComponentwsdlcallfunction()
    {
        return $this->componentwsdlcallfunction;
    }

    /**
     * Set componentdescription
     *
     * @param string $componentdescription
     * @return Component
     */
    public function setComponentdescription($componentdescription)
    {
        $this->componentdescription = $componentdescription;

        return $this;
    }

    /**
     * Get componentdescription
     *
     * @return string 
     */
    public function getComponentdescription()
    {
        return $this->componentdescription;
    }

    /**
     * Set componentaltdescription
     *
     * @param string $componentaltdescription
     * @return Component
     */
    public function setComponentaltdescription($componentaltdescription)
    {
        $this->componentaltdescription = $componentaltdescription;

        return $this;
    }

    /**
     * Get componentaltdescription
     *
     * @return string 
     */
    public function getComponentaltdescription()
    {
        return $this->componentaltdescription;
    }

    /**
     * Set componentlabellt
     *
     * @param string $componentlabellt
     * @return Component
     */
    public function setComponentlabellt($componentlabellt)
    {
        $this->componentlabellt = $componentlabellt;

        return $this;
    }

    /**
     * Get componentlabellt
     *
     * @return string 
     */
    public function getComponentlabellt()
    {
        return $this->componentlabellt;
    }

    /**
     * Set componentlabelen
     *
     * @param string $componentlabelen
     * @return Component
     */
    public function setComponentlabelen($componentlabelen)
    {
        $this->componentlabelen = $componentlabelen;

        return $this;
    }

    /**
     * Get componentlabelen
     *
     * @return string 
     */
    public function getComponentlabelen()
    {
        return $this->componentlabelen;
    }

    /**
     * Get componentid
     *
     * @return integer 
     */
    public function getComponentid()
    {
        return $this->componentid;
    }

    /**
     * Set clusterid
     *
     * @param \Damis\EntitiesBundle\Entity\Cluster $clusterid
     * @return Component
     */
    public function setClusterid(\Damis\EntitiesBundle\Entity\Cluster $clusterid = null)
    {
        $this->clusterid = $clusterid;

        return $this;
    }

    /**
     * Get clusterid
     *
     * @return \Damis\EntitiesBundle\Entity\Cluster 
     */
    public function getClusterid()
    {
        return $this->clusterid;
    }

    /**
     * Set componenttypeid
     *
     * @param \Damis\EntitiesBundle\Entity\Componenttype $componenttypeid
     * @return Component
     */
    public function setComponenttypeid(\Damis\EntitiesBundle\Entity\Componenttype $componenttypeid = null)
    {
        $this->componenttypeid = $componenttypeid;

        return $this;
    }

    /**
     * Get componenttypeid
     *
     * @return \Damis\EntitiesBundle\Entity\Componenttype 
     */
    public function getComponenttypeid()
    {
        return $this->componenttypeid;
    }
}
