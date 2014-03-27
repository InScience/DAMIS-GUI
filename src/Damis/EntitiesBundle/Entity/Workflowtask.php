<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Workflowtask
 *
 * @ORM\Table(name="workflowtask", uniqueConstraints={@ORM\UniqueConstraint(name="WORKFLOWTASK_PK", columns={"WorkflowTaskID"})}, indexes={@ORM\Index(name="FK_WORKFLOWTASK_EXPERIMENT", columns={"ExperimentID"})})
 * @ORM\Entity(repositoryClass="Damis\EntitiesBundle\Entity\WorkflowtaskRepository")
 */
class Workflowtask
{
    /**
     * @var integer
     *
     * @ORM\Column(name="WorkflowTaskIsRunning", type="integer", nullable=false)
     */
    private $workflowtaskisrunning;

    /**
     * @var integer
     *
     * @ORM\Column(name="WorkflowTaskID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $workflowtaskid;

    /**
     * @var \Damis\ExperimentBundle\Entity\Experiment
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\Experiment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ExperimentID", referencedColumnName="ExperimentID", onDelete="CASCADE")
     * })
     */
    private $experiment;

    /**
     * @var string
     *
     * @ORM\Column(name="TaskBox", type="string", length=256, nullable=true)
     */
    private $taskBox;

    /**
     * @var string
     *
     * @ORM\Column(name="Message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var integer
     *
     * @ORM\Column(name="ExecutionTime", type="integer", nullable=true)
     */
    private $executionTime;

    /**
     * @ORM\OneToMany(targetEntity="Damis\EntitiesBundle\Entity\Parametervalue", mappedBy="workflowtask")
     */
    private $parameterValues;

    /**
     * Constructor
     */
    public function __construct() {
        $this->parameterValues = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @param mixed $parameterValues
     */
    public function setParameterValues($parameterValues) {
        $this->parameterValues = $parameterValues;
    }

    /**
     * @return mixed
     */
    public function getParameterValues() {
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
     * @param \Damis\ExperimentBundle\Entity\Experiment $experiment
     * @return Workflowtask
     */
    public function setExperiment(\Damis\ExperimentBundle\Entity\Experiment $experiment = null)
    {
        $this->experiment = $experiment;

        return $this;
    }

    /**
     * Get experiment
     *
     * @return \Damis\ExperimentBundle\Entity\Experiment
     */
    public function getExperiment()
    {
        return $this->experiment;
    }

    /**
     * @param int $executionTime
     */
    public function setExecutionTime($executionTime) {
        $this->executionTime = $executionTime;
    }

    /**
     * @return int
     */
    public function getExecutionTime() {
        return $this->executionTime;
    }

    /**
     * @param string $message
     */
    public function setMessage($message) {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @param string $taskBox
     */
    public function setTaskBox($taskBox) {
        $this->taskBox = $taskBox;
    }

    /**
     * @return string
     */
    public function getTaskBox() {
        return $this->taskBox;
    }

}
