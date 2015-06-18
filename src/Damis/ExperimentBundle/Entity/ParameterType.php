<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ParameterType
 *
 * @ORM\Table(name="parametertype", uniqueConstraints={@ORM\UniqueConstraint(name="PARAMETERTYPE_PK", columns={"ParameterTypeID"})})
 * @ORM\Entity
 */
class ParameterType
{
    /**
     * @var string
     *
     * @ORM\Column(name="ParameterType", type="string", length=80, nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="ParameterTypeID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set type
     *
     * @param string $type
     * @return ParameterType
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->Type;
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
}
