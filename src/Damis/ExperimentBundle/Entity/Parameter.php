<?php

namespace Damis\ExperimentBundle\Entity;

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
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="ParameterIsRequired", type="integer", nullable=false)
     */
    private $isRequired;

    /**
     * @var string
     *
     * @ORM\Column(name="ParameterDefault", type="string", length=80, nullable=true)
     */
    private $default;

    /**
     * @var string
     *
     * @ORM\Column(name="ParameterDescription", type="string", length=500, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="ParameterLabelLT", type="string", length=255, nullable=true)
     */
    private $labelLt;

    /**
     * @var string
     *
     * @ORM\Column(name="ParameterLabelEN", type="string", length=255, nullable=true)
     */
    private $labelEn;

    /**
     * @var integer
     *
     * @ORM\Column(name="ParameterID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Damis\ExperimentBundle\Entity\ParameterType
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\ParameterType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ParameterTypeID", referencedColumnName="ParameterTypeID")
     * })
     */
    private $type;

    /**
     * @var ParameterConnectionType
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\ParameterConnectionType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ParameterConnectionTypeID", referencedColumnName="ParameterConnectionTypeID")
     * })
     */
    private $connectionType;

    /**
     * @var Component
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ComponentID", referencedColumnName="ComponentID")
     * })
     */
    private $component;



    /**
     * Set name
     *
     * @param string $name
     * @return Parameter
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set isRequired
     *
     * @param integer $isRequired
     * @return Parameter
     */
    public function setIsRequired($isRequired)
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * Get isRequired
     *
     * @return integer 
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * Set default
     *
     * @param string $default
     * @return Parameter
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Get default
     *
     * @return string 
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Parameter
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
     * Set labellt
     *
     * @param string $labelLt
     * @return Parameter
     */
    public function setLabelLt($labelLt)
    {
        $this->labelLt = $labelLt;

        return $this;
    }

    /**
     * Get labelLt
     *
     * @return string 
     */
    public function getLabelLt()
    {
        return $this->labelLt;
    }

    /**
     * Set labelEn
     *
     * @param string $labelEn
     * @return Parameter
     */
    public function setLabelEn($labelEn)
    {
        $this->labelEn = $labelEn;

        return $this;
    }

    /**
     * Get labelEn
     *
     * @return string 
     */
    public function getLabelEn()
    {
        return $this->labelEn;
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
     * Set type
     *
     * @param ParameterType $type
     * @return Parameter
     */
    public function setType(ParameterType $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return ParameterType
     */
    public function getParameterType()
    {
        return $this->type;
    }

    /**
     * Set connectionType
     *
     * @param ParameterConnectionType $connectionType
     * @return Parameter
     */
    public function setParameterConnectionType(ParameterConnectionType $connectionType = null)
    {
        $this->connectionType = $connectionType;

        return $this;
    }

    /**
     * Get connectionType
     *
     * @return \Damis\ExperimentBundle\Entity\Parameterconnectiontype
     */
    public function getConnectionType()
    {
        return $this->connectionType;
    }

    /**
     * Set component
     *
     * @param Component $component
     * @return Parameter
     */
    public function setComponent(Component $component = null)
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get component
     *
     * @return Component
     */
    public function getComponent()
    {
        return $this->component;
    }
}
