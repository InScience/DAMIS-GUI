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
     *   @ORM\JoinColumn(name="WorkflowTaskID", referencedColumnName="WorkflowTaskID")
     * })
     */
    private $workflowtaskid;

    /**
     * @var \Damis\ExperimentBundle\Entity\Parameter
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\Parameter")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ParameterID", referencedColumnName="ParameterID")
     * })
     */
    private $parameterid;



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
     * Set workflowtaskid
     *
     * @param \Damis\EntitiesBundle\Entity\Workflowtask $workflowtaskid
     * @return Parametervalue
     */
    public function setWorkflowtaskid(\Damis\EntitiesBundle\Entity\Workflowtask $workflowtaskid = null)
    {
        $this->workflowtaskid = $workflowtaskid;

        return $this;
    }

    /**
     * Get workflowtaskid
     *
     * @return \Damis\EntitiesBundle\Entity\Workflowtask 
     */
    public function getWorkflowtaskid()
    {
        return $this->workflowtaskid;
    }

    /**
     * Set parameterid
     *
     * @param \Damis\EntitiesBundle\Entity\Parameter $parameterid
     * @return Parametervalue
     */
    public function setParameterid(\Damis\EntitiesBundle\Entity\Parameter $parameterid = null)
    {
        $this->parameterid = $parameterid;

        return $this;
    }

    /**
     * Get parameterid
     *
     * @return \Damis\EntitiesBundle\Entity\Parameter 
     */
    public function getParameterid()
    {
        return $this->parameterid;
    }
}
