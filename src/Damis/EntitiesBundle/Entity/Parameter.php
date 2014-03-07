<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Parameter
 *
 * @ORM\Table(name="parameter", uniqueConstraints={@ORM\UniqueConstraint(name="PARAMETER_PK", columns={"ParameterID"})}, indexes={@ORM\Index(name="FK_PARAMETER_PARAMTYPERTYPE", columns={"ParameterTypeID"}), @ORM\Index(name="FK_PARAMETER_PARAMCONNTYPE", columns={"ParameterConnectionTypeID"}), @ORM\Index(name="FK_PARAMETER_COMPONENT", columns={"ComponentID"})})
 * @ORM\Entity
 */
class Parameter
{
    /**
     * @var string
     *
     * @ORM\Column(name="ParameterName", type="string", length=80, nullable=false)
     */
    private $parametername;

    /**
     * @var integer
     *
     * @ORM\Column(name="ParameterIsRequired", type="integer", nullable=false)
     */
    private $parameterisrequired;

    /**
     * @var string
     *
     * @ORM\Column(name="ParameterDefault", type="string", length=80, nullable=true)
     */
    private $parameterdefault;

    /**
     * @var string
     *
     * @ORM\Column(name="ParameterDescription", type="string", length=500, nullable=true)
     */
    private $parameterdescription;

    /**
     * @var string
     *
     * @ORM\Column(name="ParameterLabelLT", type="string", length=255, nullable=true)
     */
    private $parameterlabellt;

    /**
     * @var string
     *
     * @ORM\Column(name="ParameterLabelEN", type="string", length=255, nullable=true)
     */
    private $parameterlabelen;

    /**
     * @var integer
     *
     * @ORM\Column(name="ParameterID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $parameterid;

    /**
     * @var \Damis\EntitiesBundle\Entity\Parametertype
     *
     * @ORM\ManyToOne(targetEntity="Damis\EntitiesBundle\Entity\Parametertype")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ParameterTypeID", referencedColumnName="ParameterTypeID")
     * })
     */
    private $parametertypeid;

    /**
     * @var \Damis\EntitiesBundle\Entity\Parameterconnectiontype
     *
     * @ORM\ManyToOne(targetEntity="Damis\EntitiesBundle\Entity\Parameterconnectiontype")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ParameterConnectionTypeID", referencedColumnName="ParameterConnectionTypeID")
     * })
     */
    private $parameterconnectiontypeid;

    /**
     * @var \Damis\EntitiesBundle\Entity\Component
     *
     * @ORM\ManyToOne(targetEntity="Damis\EntitiesBundle\Entity\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ComponentID", referencedColumnName="ComponentID")
     * })
     */
    private $componentid;



    /**
     * Set parametername
     *
     * @param string $parametername
     * @return Parameter
     */
    public function setParametername($parametername)
    {
        $this->parametername = $parametername;

        return $this;
    }

    /**
     * Get parametername
     *
     * @return string 
     */
    public function getParametername()
    {
        return $this->parametername;
    }

    /**
     * Set parameterisrequired
     *
     * @param integer $parameterisrequired
     * @return Parameter
     */
    public function setParameterisrequired($parameterisrequired)
    {
        $this->parameterisrequired = $parameterisrequired;

        return $this;
    }

    /**
     * Get parameterisrequired
     *
     * @return integer 
     */
    public function getParameterisrequired()
    {
        return $this->parameterisrequired;
    }

    /**
     * Set parameterdefault
     *
     * @param string $parameterdefault
     * @return Parameter
     */
    public function setParameterdefault($parameterdefault)
    {
        $this->parameterdefault = $parameterdefault;

        return $this;
    }

    /**
     * Get parameterdefault
     *
     * @return string 
     */
    public function getParameterdefault()
    {
        return $this->parameterdefault;
    }

    /**
     * Set parameterdescription
     *
     * @param string $parameterdescription
     * @return Parameter
     */
    public function setParameterdescription($parameterdescription)
    {
        $this->parameterdescription = $parameterdescription;

        return $this;
    }

    /**
     * Get parameterdescription
     *
     * @return string 
     */
    public function getParameterdescription()
    {
        return $this->parameterdescription;
    }

    /**
     * Set parameterlabellt
     *
     * @param string $parameterlabellt
     * @return Parameter
     */
    public function setParameterlabellt($parameterlabellt)
    {
        $this->parameterlabellt = $parameterlabellt;

        return $this;
    }

    /**
     * Get parameterlabellt
     *
     * @return string 
     */
    public function getParameterlabellt()
    {
        return $this->parameterlabellt;
    }

    /**
     * Set parameterlabelen
     *
     * @param string $parameterlabelen
     * @return Parameter
     */
    public function setParameterlabelen($parameterlabelen)
    {
        $this->parameterlabelen = $parameterlabelen;

        return $this;
    }

    /**
     * Get parameterlabelen
     *
     * @return string 
     */
    public function getParameterlabelen()
    {
        return $this->parameterlabelen;
    }

    /**
     * Get parameterid
     *
     * @return integer 
     */
    public function getParameterid()
    {
        return $this->parameterid;
    }

    /**
     * Set parametertypeid
     *
     * @param \Damis\EntitiesBundle\Entity\Parametertype $parametertypeid
     * @return Parameter
     */
    public function setParametertypeid(\Damis\EntitiesBundle\Entity\Parametertype $parametertypeid = null)
    {
        $this->parametertypeid = $parametertypeid;

        return $this;
    }

    /**
     * Get parametertypeid
     *
     * @return \Damis\EntitiesBundle\Entity\Parametertype 
     */
    public function getParametertypeid()
    {
        return $this->parametertypeid;
    }

    /**
     * Set parameterconnectiontypeid
     *
     * @param \Damis\EntitiesBundle\Entity\Parameterconnectiontype $parameterconnectiontypeid
     * @return Parameter
     */
    public function setParameterconnectiontypeid(\Damis\EntitiesBundle\Entity\Parameterconnectiontype $parameterconnectiontypeid = null)
    {
        $this->parameterconnectiontypeid = $parameterconnectiontypeid;

        return $this;
    }

    /**
     * Get parameterconnectiontypeid
     *
     * @return \Damis\EntitiesBundle\Entity\Parameterconnectiontype 
     */
    public function getParameterconnectiontypeid()
    {
        return $this->parameterconnectiontypeid;
    }

    /**
     * Set componentid
     *
     * @param \Damis\EntitiesBundle\Entity\Component $componentid
     * @return Parameter
     */
    public function setComponentid(\Damis\EntitiesBundle\Entity\Component $componentid = null)
    {
        $this->componentid = $componentid;

        return $this;
    }

    /**
     * Get componentid
     *
     * @return \Damis\EntitiesBundle\Entity\Component 
     */
    public function getComponentid()
    {
        return $this->componentid;
    }
}
