<?php

namespace Damis\ExperimentBundle\Entity;

use Base\UserBundle\Entity\User;
use Damis\EntitiesBundle\Entity\Workflowtask;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Experiment
 */
#[ORM\Table(name: 'experiment')]
#[ORM\Index(name: 'FK_EXPERIMET_EXPERIMENTSTATUS', columns: ['ExperimentStatusID'])]
#[ORM\Index(name: 'FK_EXPERIMET_DAMISUSER', columns: ['UserID'])]
#[ORM\UniqueConstraint(name: 'EXPERIMENT_PK', columns: ['ExperimentID'])]
#[ORM\Entity(repositoryClass: ExperimentRepository::class)]
class Experiment
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'ExperimentName', type: 'string', length: 80, nullable: false)]
    private $name;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ExperimentMaxDuration', type: 'time', nullable: true)]
    private $maxDuration;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'ExpermentStart', type: 'integer', nullable: true)]
    private $start;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'ExperimentFinish', type: 'integer', nullable: true)]
    private $finish;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'ExperimentUseCPU', type: 'integer', nullable: true)]
    private $useCpu;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'ExperimentUsePrimaryMemory', type: 'integer', nullable: true)]
    private $usePrimaryMemory;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'ExperimentUseSecMemory', type: 'integer', nullable: true)]
    private $useSecMemory;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ExperimentGUIData', type: 'text', nullable: true)]
    private $guiData;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'ExperimentID', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var Experimentstatus
     *
     *
     */
    #[ORM\JoinColumn(name: 'ExperimentStatusID', referencedColumnName: 'ExperimentStatusID')]
    #[ORM\ManyToOne(targetEntity: Experimentstatus::class)]
    private $status;

    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'UserID', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;

    #[ORM\OneToMany(targetEntity: Workflowtask::class, mappedBy: 'experiment')]
    private $workflowtasks;

    /**
     * Set name
     *
     * @param string $name
     * @return Experiment
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
     * Set maxDuration
     *
     * @param \DateTime $maxDuration
     * @return Experiment
     */
    public function setMaxDuration($maxDuration)
    {
        $this->maxDuration = $maxDuration;

        return $this;
    }

    /**
     * Get maxduration
     *
     * @return \DateTime
     */
    public function getMaxDuration()
    {
        return $this->maxDuration;
    }

    /**
     * Set start
     *
     * @param integer $start
     * @return Experiment
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get expermentstart
     *
     * @return integer
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set finish
     *
     * @param integer $finish
     * @return Experiment
     */
    public function setFinish($finish)
    {
        $this->finish = $finish;

        return $this;
    }

    /**
     * Get finish
     *
     * @return integer
     */
    public function getFinish()
    {
        return $this->finish;
    }

    /**
     * Set useCpu
     *
     * @param integer $useCpu
     * @return Experiment
     */
    public function setUseCpu($useCpu)
    {
        $this->useCpu = $useCpu;

        return $this;
    }

    /**
     * Get useCpu
     *
     * @return integer
     */
    public function getUseCpu()
    {
        return $this->useCpu;
    }

    /**
     * Set usePrimaryMemory
     *
     * @param integer $usePrimaryMemory
     * @return Experiment
     */
    public function setUsePrimaryMemory($usePrimaryMemory)
    {
        $this->usePrimaryMemory = $usePrimaryMemory;

        return $this;
    }

    /**
     * Get usePrimaryMemory
     *
     * @return integer
     */
    public function getUsePrimaryMemory()
    {
        return $this->usePrimaryMemory;
    }

    /**
     * Set useSecMemory
     *
     * @param integer $useSecMemory
     * @return Experiment
     */
    public function setUseSecMemory($useSecMemory)
    {
        $this->useSecMemory = $useSecMemory;

        return $this;
    }

    /**
     * Get useSecMemory
     *
     * @return integer
     */
    public function getUseSecMemory()
    {
        return $this->useSecMemory;
    }

    /**
     * Set GuiData
     *
     * @param string $guiData
     * @return Experiment
     */
    public function setGuiData($guiData)
    {
        $this->guiData = $guiData;

        return $this;
    }

    /**
     * Get guidata
     *
     * @return string
     */
    public function getGuiData()
    {
        return $this->guiData;
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
     * Set status
     *
     * @param Experimentstatus $status
     * @return Experiment
     */
    public function setStatus(Experimentstatus $status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return Experimentstatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Experiment
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->workflowtasks = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getWorkflowtasks()
    {
        return $this->workflowtasks;
    }
}
