<?php

namespace Damis\EntitiesBundle\Entity;

use Damis\ExperimentBundle\Entity\Experiment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Workflowtask
 */
#[ORM\Table(name: 'workflowtask')]
#[ORM\Index(name: 'FK_WORKFLOWTASK_EXPERIMENT', columns: ['ExperimentID'])]
#[ORM\UniqueConstraint(name: 'WORKFLOWTASK_PK', columns: ['WorkflowTaskID'])]
#[ORM\Entity(repositoryClass: WorkflowtaskRepository::class)]
class Workflowtask
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'WorkflowTaskIsRunning', type: 'integer', nullable: false)]
    private $workflowtaskisrunning;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'WorkflowTaskID', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $workflowtaskid;

    /**
     * @var Experiment
     */
    #[ORM\JoinColumn(name: 'ExperimentID', referencedColumnName: 'ExperimentID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Experiment::class, inversedBy: 'workflowtasks')]
    private $experiment;

    /**
     * @var string
     */
    #[ORM\Column(name: 'TaskBox', type: 'string', length: 256, nullable: true)]
    private $taskBox;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Message', type: 'text', nullable: true)]
    private $message;

    /**
     * @var float
     */
    #[ORM\Column(name: 'ExecutionTime', type: 'float', nullable: true)]
    private $executionTime;

    #[ORM\OneToMany(targetEntity: Parametervalue::class, mappedBy: 'workflowtask')]
    private $parameterValues;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->parameterValues = new ArrayCollection();
    }

    public function setParameterValues(mixed $parameterValues)
    {
        $this->parameterValues = $parameterValues;
    }

    /**
     * @return mixed
     */
    public function getParameterValues()
    {
        return $this->parameterValues;
    }


    /**
     * Set workflowtaskisrunning
     *
     * @param integer $workflowtaskisrunning
     * @return Workflowtask
     */
    public function setWorkflowtaskisrunning($workflowtaskisrunning)
    {
        $this->workflowtaskisrunning = $workflowtaskisrunning;

        return $this;
    }

    /**
     * Get workflowtaskisrunning
     *
     * @return integer
     */
    public function getWorkflowtaskisrunning()
    {
        return $this->workflowtaskisrunning;
    }

    /**
     * Get workflowtaskid
     *
     * @return integer
     */
    public function getWorkflowtaskid()
    {
        return $this->workflowtaskid;
    }

    /**
     * Set experiment
     *
     * @param Experiment $experiment
     * @return Workflowtask
     */
    public function setExperiment(Experiment $experiment = null)
    {
        $this->experiment = $experiment;

        return $this;
    }

    /**
     * Get experiment
     *
     * @return Experiment
     */
    public function getExperiment()
    {
        return $this->experiment;
    }

    /**
     * @param int $executionTime
     */
    public function setExecutionTime($executionTime)
    {
        $this->executionTime = $executionTime;
    }

    /**
     * @return int
     */
    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $taskBox
     */
    public function setTaskBox($taskBox)
    {
        $this->taskBox = $taskBox;
    }

    /**
     * @return string
     */
    public function getTaskBox()
    {
        return $this->taskBox;
    }
}
