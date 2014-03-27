<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Component
 *
 * @ORM\Table(name="component", uniqueConstraints={@ORM\UniqueConstraint(name="COMPONENT_PK", columns={"ComponentID"})}, indexes={@ORM\Index(name="FK_COMPONENT_CLUSTER", columns={"ClusterID"}), @ORM\Index(name="FK_COMPONENT_COMPONENTTYPE", columns={"ComponentTypeID"})})
 * @ORM\Entity(repositoryClass="Damis\ExperimentBundle\Entity\ComponentRepository")
 */
class Component
{
    /**
     * @var string
     *
     * @ORM\Column(name="ComponentName", type="string", length=80, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentIcon", type="string", length=255, nullable=false)
     */
    private $icon;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentWSDLRunHost", type="string", length=255, nullable=false)
     */
    private $wsdlRunHost;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentWSDLCallFunction", type="string", length=80, nullable=false)
     */
    private $wsdlCallFunction;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentDescription", type="string", length=500, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentAltDescription", type="string", length=80, nullable=true)
     */
    private $altDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentLabelLT", type="string", length=255, nullable=true)
     */
    private $labelLt;

    /**
     * @var string
     *
     * @ORM\Column(name="ComponentLabelEN", type="string", length=255, nullable=true)
     */
    private $labelEn;

    /**
     * @var integer
     *
     * @ORM\Column(name="ComponentID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Damis\ExperimentBundle\Entity\Cluster
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\Cluster")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ClusterID", referencedColumnName="ClusterID")
     * })
     */
    private $clusterId;

    /**
     * @var \Damis\ExperimentBundle\Entity\ComponentType
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\ComponentType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ComponentTypeID", referencedColumnName="ComponentTypeID")
     * })
     */
    private $typeId;

    /**
     * @var string
     *
     * @ORM\Column(name="FormType", type="string", length=255, nullable=true)
     */
    private $formType;

    /**
     * @ORM\OneToMany(targetEntity="Damis\ExperimentBundle\Entity\Parameter" , mappedBy="component")
     */
    private $parameters;

    /**
     * @return mixed
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Component
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
         * Set icon
     *
     * @param string $icon
     * @return Component
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set wsdlRunHost
     *
     * @param string $wsdlRunHost
     * @return Component
     */
    public function setWsdlRunHost($wsdlRunHost)
    {
        $this->wsdlRunHost = $wsdlRunHost;

        return $this;
    }

    /**
     * Get wsdlRunHost
     *
     * @return string
     */
    public function getWsdlRunHost()
    {
        return $this->wsdlRunHost;
    }

    /**
     * Set wsdlCallFunction
     *
     * @param string $wsdlCallFunction
     * @return Component
     */
    public function setWsdlCallFunction($wsdlCallFunction)
    {
        $this->wsdlCallFunction = $wsdlCallFunction;

        return $this;
    }

    /**
     * Get wsdlCallFunction
     *
     * @return string
     */
    public function getWsdlCallFunction()
    {
        return $this->wsdlCallFunction;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Component
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
     * Set altDescription
     *
     * @param string $altDescription
     * @return Component
     */
    public function setAltDescription($altDescription)
    {
        $this->altDescription = $altDescription;

        return $this;
    }

    /**
     * Get altDescription
     *
     * @return string
     */
    public function getAltDescription()
    {
        return $this->altDescription;
    }

    /**
     * Set labelLt
     *
     * @param string $labelLt
     * @return Component
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
     * @return Component
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
     * Set clusterId
     *
     * @param Cluster $clusterId
     * @return Component
     */
    public function setClusterId(Cluster $clusterId = null)
    {
        $this->clusterId = $clusterId;

        return $this;
    }

    /**
     * Get clusterId
     *
     * @return Cluster
     */
    public function getClusterId()
    {
        return $this->clusterId;
    }

    /**
     * Set typeId
     *
     * @param ComponentType $typeId
     * @return Component
     */
    public function setTypeId(ComponentType $typeId = null)
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * Get typeId
     *
     * @return ComponentType
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param string $formType
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->parameters = new \Doctrine\Common\Collections\ArrayCollection();
    }

}