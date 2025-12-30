<?php

namespace Damis\EntitiesBundle\Entity;

use Damis\ExperimentBundle\Entity\Parameter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Parametervalue
 */
#[ORM\Table(name: 'parametervalue')]
#[ORM\Index(name: 'FK_PARAMETERVALUE_WORKFLOWTASK', columns: ['WorkflowTaskID'])]
#[ORM\Index(name: 'FK_PARAMETERVALUE_PARAMETER', columns: ['ParameterID'])]
#[ORM\UniqueConstraint(name: 'PARAMETERVALUE_PK', columns: ['ParameterValueID'])]
#[ORM\Entity(repositoryClass: ParametervalueRepository::class)]
class Parametervalue
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'ParameterValue', type: 'string', length: 255, nullable: true)]
    private $parametervalue;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'ParameterValueID', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $parametervalueid;

    /**
     * @var Workflowtask
     */
    #[ORM\JoinColumn(name: 'WorkflowTaskID', referencedColumnName: 'WorkflowTaskID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Workflowtask::class, inversedBy: 'parameterValues')]
    private $workflowtask;

    /**
     * @var Parameter
     */
    #[ORM\JoinColumn(name: 'ParameterID', referencedColumnName: 'ParameterID')]
    #[ORM\ManyToOne(targetEntity: Parameter::class)]
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
     * @param Workflowtask $workflowtask
     * @return Parametervalue
     */
    public function setWorkflowtask(Workflowtask $workflowtask = null)
    {
        $this->workflowtask = $workflowtask;

        return $this;
    }

    /**
     * Get workflowtask
     *
     * @return Workflowtask
     */
    public function getWorkflowtask()
    {
        return $this->workflowtask;
    }

    /**
     * Set parameter
     *
     * @param Parameter $parameter
     * @return Parametervalue
     */
    public function setParameter(Parameter $parameter = null)
    {
        $this->parameter = $parameter;

        return $this;
    }

    /**
     * Get parameter
     *
     * @return Parameter
     */
    public function getParameter()
    {
        return $this->parameter;
    }
}
