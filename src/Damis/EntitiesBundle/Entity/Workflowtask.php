<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Workflowtask
 *
 * @ORM\Table(name="workflowtask", uniqueConstraints={@ORM\UniqueConstraint(name="WORKFLOWTASK_PK", columns={"WorkflowTaskID"})}, indexes={@ORM\Index(name="FK_WORKFLOWTASK_EXPERIMENT", columns={"ExperimentID"})})
 * @ORM\Entity
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
     *   @ORM\JoinColumn(name="ExperimentID", referencedColumnName="ExperimentID")
     * })
     */
    private $experimentid;



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
     * Set experimentid
     *
     * @param \Damis\ExperimentBundle\Entity\Experiment $experimentid
     * @return Workflowtask
     */
    public function setExperimentid(\Damis\ExperimentBundle\Entity\Experiment $experimentid = null)
    {
        $this->experimentid = $experimentid;

        return $this;
    }

    /**
     * Get experimentid
     *
     * @return \Damis\ExperimentBundle\Entity\Experiment
     */
    public function getExperimentid()
    {
        return $this->experimentid;
    }
}
