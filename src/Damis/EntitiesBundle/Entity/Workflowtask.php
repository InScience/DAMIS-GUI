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
}
