<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * Experiment
 *
 * @ORM\Table(name="experiment", uniqueConstraints={@ORM\UniqueConstraint(name="EXPERIMENT_PK", columns={"ExperimentID"})}, indexes={@ORM\Index(name="FK_EXPERIMET_EXPERIMENTSTATUS", columns={"ExperimentStatusID"}), @ORM\Index(name="FK_EXPERIMET_DAMISUSER", columns={"UserID"})})
 * @ORM\Entity
 * @GRID\Source(columns="experimentid, experimentname, experimentstatusid.experimentstatus")
 */
class Experiment
{
    /**
     * @var string
     *
     * @ORM\Column(name="ExperimentName", type="string", length=80, nullable=false)
     */
    private $experimentname;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ExperimentMaxDuration", type="time", nullable=true)
     */
    private $experimentmaxduration;

    /**
     * @var integer
     *
     * @ORM\Column(name="ExpermentStart", type="integer", nullable=true)
     */
    private $expermentstart;

    /**
     * @var integer
     *
     * @ORM\Column(name="ExperimentFinish", type="integer", nullable=true)
     */
    private $experimentfinish;

    /**
     * @var integer
     *
     * @ORM\Column(name="ExperimentUseCPU", type="integer", nullable=true)
     */
    private $experimentusecpu;

    /**
     * @var integer
     *
     * @ORM\Column(name="ExperimentUsePrimaryMemory", type="integer", nullable=true)
     */
    private $experimentuseprimarymemory;

    /**
     * @var integer
     *
     * @ORM\Column(name="ExperimentUseSecMemory", type="integer", nullable=true)
     */
    private $experimentusesecmemory;

    /**
     * @var string
     *
     * @ORM\Column(name="ExperimentGUIData", type="text", nullable=true)
     */
    private $experimentguidata;

    /**
     * @var integer
     *
     * @ORM\Column(name="ExperimentID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $experimentid;

    /**
     * @var \Damis\ExperimentBundle\Entity\Experimentstatus
     *
     * @ORM\ManyToOne(targetEntity="Damis\ExperimentBundle\Entity\Experimentstatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ExperimentStatusID", referencedColumnName="ExperimentStatusID")
     * })
     *
     * @GRID\Column(field="experimentstatusid.experimentstatus", type="text")
     */
    private $experimentstatusid;

    /**
     * @var \Base\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Base\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="id")
     * })
     */
    private $userid;



    /**
     * Set experimentname
     *
     * @param string $experimentname
     * @return Experiment
     */
    public function setExperimentname($experimentname)
    {
        $this->experimentname = $experimentname;

        return $this;
    }

    /**
     * Get experimentname
     *
     * @return string
     */
    public function getExperimentname()
    {
        return $this->experimentname;
    }

    /**
     * Set experimentmaxduration
     *
     * @param \DateTime $experimentmaxduration
     * @return Experiment
     */
    public function setExperimentmaxduration($experimentmaxduration)
    {
        $this->experimentmaxduration = $experimentmaxduration;

        return $this;
    }

    /**
     * Get experimentmaxduration
     *
     * @return \DateTime
     */
    public function getExperimentmaxduration()
    {
        return $this->experimentmaxduration;
    }

    /**
     * Set expermentstart
     *
     * @param integer $expermentstart
     * @return Experiment
     */
    public function setExpermentstart($expermentstart)
    {
        $this->expermentstart = $expermentstart;

        return $this;
    }

    /**
     * Get expermentstart
     *
     * @return integer
     */
    public function getExpermentstart()
    {
        return $this->expermentstart;
    }

    /**
     * Set experimentfinish
     *
     * @param integer $experimentfinish
     * @return Experiment
     */
    public function setExperimentfinish($experimentfinish)
    {
        $this->experimentfinish = $experimentfinish;

        return $this;
    }

    /**
     * Get experimentfinish
     *
     * @return integer
     */
    public function getExperimentfinish()
    {
        return $this->experimentfinish;
    }

    /**
     * Set experimentusecpu
     *
     * @param integer $experimentusecpu
     * @return Experiment
     */
    public function setExperimentusecpu($experimentusecpu)
    {
        $this->experimentusecpu = $experimentusecpu;

        return $this;
    }

    /**
     * Get experimentusecpu
     *
     * @return integer
     */
    public function getExperimentusecpu()
    {
        return $this->experimentusecpu;
    }

    /**
     * Set experimentuseprimarymemory
     *
     * @param integer $experimentuseprimarymemory
     * @return Experiment
     */
    public function setExperimentuseprimarymemory($experimentuseprimarymemory)
    {
        $this->experimentuseprimarymemory = $experimentuseprimarymemory;

        return $this;
    }

    /**
     * Get experimentuseprimarymemory
     *
     * @return integer
     */
    public function getExperimentuseprimarymemory()
    {
        return $this->experimentuseprimarymemory;
    }

    /**
     * Set experimentusesecmemory
     *
     * @param integer $experimentusesecmemory
     * @return Experiment
     */
    public function setExperimentusesecmemory($experimentusesecmemory)
    {
        $this->experimentusesecmemory = $experimentusesecmemory;

        return $this;
    }

    /**
     * Get experimentusesecmemory
     *
     * @return integer
     */
    public function getExperimentusesecmemory()
    {
        return $this->experimentusesecmemory;
    }

    /**
     * Set experimentguidata
     *
     * @param string $experimentguidata
     * @return Experiment
     */
    public function setExperimentguidata($experimentguidata)
    {
        $this->experimentguidata = $experimentguidata;

        return $this;
    }

    /**
     * Get experimentguidata
     *
     * @return string
     */
    public function getExperimentguidata()
    {
        return $this->experimentguidata;
    }

    /**
     * Get experimentid
     *
     * @return integer
     */
    public function getExperimentid()
    {
        return $this->experimentid;
    }

    /**
     * Set experimentstatusid
     *
     * @param \Damis\ExperimentBundle\Entity\Experimentstatus $experimentstatusid
     * @return Experiment
     */
    public function setExperimentstatusid(\Damis\ExperimentBundle\Entity\Experimentstatus $experimentstatusid = null)
    {
        $this->experimentstatusid = $experimentstatusid;

        return $this;
    }

    /**
     * Get experimentstatusid
     *
     * @return \Damis\ExperimentBundle\Entity\Experimentstatus
     */
    public function getExperimentstatusid()
    {
        return $this->experimentstatusid;
    }

    /**
     * Set userid
     *
     * @param \Base\UserBundle\Entity\User $userid
     * @return Experiment
     */
    public function setUserid(\Base\UserBundle\Entity\User $userid = null)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid
     *
     * @return \Base\UserBundle\Entity\User
     */
    public function getUserid()
    {
        return $this->userid;
    }
}
