<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Componenttype
 *
 * @ORM\Table(name="componenttype", uniqueConstraints={@ORM\UniqueConstraint(name="COMPONENTTYPE_PK", columns={"ComponentTypeID"})})
 * @ORM\Entity
 */
class Componenttype
{
    /**
     * @var string
     *
     * @ORM\Column(name="ComponentType", type="string", length=80, nullable=false)
     */
    private $componenttype;

    /**
     * @var integer
     *
     * @ORM\Column(name="ComponentTypeID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $componenttypeid;



    /**
     * Set componenttype
     *
     * @param string $componenttype
     * @return Componenttype
     */
    public function setComponenttype($componenttype)
    {
        $this->componenttype = $componenttype;

        return $this;
    }

    /**
     * Get componenttype
     *
     * @return string 
     */
    public function getComponenttype()
    {
        return $this->componenttype;
    }

    /**
     * Get componenttypeid
     *
     * @return integer 
     */
    public function getComponenttypeid()
    {
        return $this->componenttypeid;
    }
}
