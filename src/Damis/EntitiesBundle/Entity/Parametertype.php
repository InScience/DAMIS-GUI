<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Parametertype
 *
 * @ORM\Table(name="parametertype", uniqueConstraints={@ORM\UniqueConstraint(name="PARAMETERTYPE_PK", columns={"ParameterTypeID"})})
 * @ORM\Entity
 */
class Parametertype
{
    /**
     * @var string
     *
     * @ORM\Column(name="ParameterType", type="string", length=80, nullable=false)
     */
    private $parametertype;

    /**
     * @var integer
     *
     * @ORM\Column(name="ParameterTypeID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $parametertypeid;



    /**
     * Set parametertype
     *
     * @param string $parametertype
     * @return Parametertype
     */
    public function setParametertype($parametertype)
    {
        $this->parametertype = $parametertype;

        return $this;
    }

    /**
     * Get parametertype
     *
     * @return string 
     */
    public function getParametertype()
    {
        return $this->parametertype;
    }

    /**
     * Get parametertypeid
     *
     * @return integer 
     */
    public function getParametertypeid()
    {
        return $this->parametertypeid;
    }
}
