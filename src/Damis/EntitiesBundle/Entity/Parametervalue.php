<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Parametervalue
 *
 * @ORM\Table(name="parametervalue", uniqueConstraints={@ORM\UniqueConstraint(name="PARAMETERVALUE_PK", columns={"ParameterValueID"})}, indexes={@ORM\Index(name="FK_PARAMETERVALUE_WORKFLOWTASK", columns={"WorkflowTaskID"}), @ORM\Index(name="FK_PARAMETERVALUE_PARAMETER", columns={"ParameterID"})})
 * @ORM\Entity
 */
class Parametervalue
{
    /**
     * @var string
     *
     * @ORM\Column(name="ParameterValue", type="string", length=80, nullable=true)
     */
    private $parametervalue;

    /**
     * @var integer
     *
     * @ORM\Column(name="ParameterValueID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $parametervalueid;

    /**
     * @var \Damis\EntitiesBundle\Entity\Workflowtask
     *
     * @ORM\ManyToOne(targetEntity="Damis\EntitiesBundle\Entity\Workflowtask")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="WorkflowTaskID", referencedColumnName="WorkflowTaskID", onDelete="CASCADE")
     * })
     */
    private $workflowtask;

    /**
     * @var \Damis\ExperimentBundle\Entity\Parameter
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\Parameter")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ParameterID", referencedColumnName="ParameterID")
     * })
     */
    private $parameter;



    /**
     * Set parametervalue
     *
     * @param string $parametervalue
     * @return Parametervalue
     */
    public function setParametervalue($parametervalue)
    {
        $this->parametervalue = $parametervalue;

        return $this;
    }

    /**
     * Get parametervalue
     *
     * @return string
     */
    public function getParametervalue()
    {
        return $this->parametervalue;
    }

    /**
     * Get parametervalueid
     *
     * @return integer
     */
    public function getParametervalueid()
    {
        return $this->parametervalueid;
    }

    /**
     * Set workflowtask
     *
     * @param \Damis\EntitiesBundle\Entity\Workflowtask $workflowtask
     * @return Parametervalue
     */
    public function setWorkflowtask(\Damis\EntitiesBundle\Entity\Workflowtask $workflowtask = null)
    {
        $this->workflowtask = $workflowtask;

        return $this;
    }

    /**
     * Get workflowtask
     *
     * @return \Damis\EntitiesBundle\Entity\Workflowtask
     */
    public function getWorkflowtask()
    {
        return $this->workflowtask;
    }

    /**
     * Set parameter
     *
     * @param \Damis\EntitiesBundle\Entity\Parameter $parameter
     * @return Parametervalue
     */
    public function setParameter(\Damis\ExperimentBundle\Entity\Parameter $parameter = null)
    {
        $this->parameter = $parameter;

        return $this;
    }

    /**
     * Get parameter
     *
     * @return \Damis\EntitiesBundle\Entity\Parameter
     */
    public function getParameter()
    {
        return $this->parameter;
    }
}
