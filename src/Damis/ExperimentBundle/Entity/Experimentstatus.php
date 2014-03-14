<?php

namespace Damis\ExperimentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Experimentstatus
 *
 * @ORM\Table(name="experimentstatus", uniqueConstraints={@ORM\UniqueConstraint(name="EXPERIMENTSTATUS_PK", columns={"ExperimentStatusID"})})
 * @ORM\Entity
 */
class Experimentstatus
{
    /**
     * @var string
     *
     * @ORM\Column(name="ExperimentStatus", type="string", length=80, nullable=false)
     */
    private $experimentstatus;

    /**
     * @var integer
     *
     * @ORM\Column(name="ExperimentStatusID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $experimentstatusid;



    /**
     * Set experimentstatus
     *
     * @param string $experimentstatus
     * @return Experimentstatus
     */
    public function setExperimentstatus($experimentstatus)
    {
        $this->experimentstatus = $experimentstatus;

        return $this;
    }

    /**
     * Get experimentstatus
     *
     * @return string
     */
    public function getExperimentstatus()
    {
        return $this->experimentstatus;
    }

    /**
     * Get experimentstatusid
     *
     * @return integer
     */
    public function getExperimentstatusid()
    {
        return $this->experimentstatusid;
    }
}
