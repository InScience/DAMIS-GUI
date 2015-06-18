<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ParameterConnectionType
 *
 * @ORM\Table(name="parameterconnectiontype", uniqueConstraints={@ORM\UniqueConstraint(name="PARAMETERCONNECTIONTYPE_PK", columns={"ParameterConnectionTypeID"})})
 * @ORM\Entity
 */
class ParameterConnectionType
{
    /**
     * @var string
     *
     * @ORM\Column(name="ParameterConnectionType", type="string", length=80, nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="ParameterConnectionTypeID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set type
     *
     * @param string $type
     * @return ParameterConnectionType
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
        return $this->type;
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
