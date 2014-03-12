<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ComponentType
 *
 * @ORM\Table(name="componenttype", uniqueConstraints={@ORM\UniqueConstraint(name="COMPONENTTYPE_PK", columns={"ComponentTypeID"})})
 * @ORM\Entity
 */
class ComponentType
{
    /**
     * @var string
     *
     * @ORM\Column(name="ComponentType", type="string", length=80, nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="ComponentTypeID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     * Set component type
     *
     * @param $type
     * @return ComponentType
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get component type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get component type id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
}
