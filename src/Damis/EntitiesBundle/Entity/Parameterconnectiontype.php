<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Parameterconnectiontype
 *
 * @ORM\Table(name="parameterconnectiontype", uniqueConstraints={@ORM\UniqueConstraint(name="PARAMETERCONNECTIONTYPE_PK", columns={"ParameterConnectionTypeID"})})
 * @ORM\Entity
 */
class Parameterconnectiontype
{
    /**
     * @var string
     *
     * @ORM\Column(name="ParameterConnectionType", type="string", length=80, nullable=false)
     */
    private $parameterconnectiontype;

    /**
     * @var integer
     *
     * @ORM\Column(name="ParameterConnectionTypeID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $parameterconnectiontypeid;



    /**
     * Set parameterconnectiontype
     *
     * @param string $parameterconnectiontype
     * @return Parameterconnectiontype
     */
    public function setParameterconnectiontype($parameterconnectiontype)
    {
        $this->parameterconnectiontype = $parameterconnectiontype;

        return $this;
    }

    /**
     * Get parameterconnectiontype
     *
     * @return string 
     */
    public function getParameterconnectiontype()
    {
        return $this->parameterconnectiontype;
    }

    /**
     * Get parameterconnectiontypeid
     *
     * @return integer 
     */
    public function getParameterconnectiontypeid()
    {
        return $this->parameterconnectiontypeid;
    }
}
