<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dataset
 *
 * @ORM\Table(name="dataset", uniqueConstraints={@ORM\UniqueConstraint(name="DATASET_PK", columns={"DatasetID"})}, indexes={@ORM\Index(name="FK_DATASET_DAMISUSER", columns={"UserID"})})
 * @ORM\Entity
 */
class Dataset
{
    /**
     * @var integer
     *
     * @ORM\Column(name="DatasetIsMIDAS", type="integer", nullable=false)
     */
    private $datasetismidas;

    /**
     * @var string
     *
     * @ORM\Column(name="DatasetTitle", type="string", length=80, nullable=false)
     */
    private $datasettitle;

    /**
     * @var integer
     *
     * @ORM\Column(name="DatasetCreated", type="integer", nullable=false)
     */
    private $datasetcreated;

    /**
     * @var string
     *
     * @ORM\Column(name="DatsetFilepPath", type="string", length=255, nullable=false)
     */
    private $datsetfileppath;

    /**
     * @var integer
     *
     * @ORM\Column(name="DatasetUpdated", type="integer", nullable=true)
     */
    private $datasetupdated;

    /**
     * @var string
     *
     * @ORM\Column(name="DatasetDescription", type="string", length=500, nullable=true)
     */
    private $datasetdescription;

    /**
     * @var integer
     *
     * @ORM\Column(name="DatasetID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $datasetid;

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
     * Set datasetismidas
     *
     * @param integer $datasetismidas
     * @return Dataset
     */
    public function setDatasetismidas($datasetismidas)
    {
        $this->datasetismidas = $datasetismidas;

        return $this;
    }

    /**
     * Get datasetismidas
     *
     * @return integer
     */
    public function getDatasetismidas()
    {
        return $this->datasetismidas;
    }

    /**
     * Set datasettitle
     *
     * @param string $datasettitle
     * @return Dataset
     */
    public function setDatasettitle($datasettitle)
    {
        $this->datasettitle = $datasettitle;

        return $this;
    }

    /**
     * Get datasettitle
     *
     * @return string
     */
    public function getDatasettitle()
    {
        return $this->datasettitle;
    }

    /**
     * Set datasetcreated
     *
     * @param integer $datasetcreated
     * @return Dataset
     */
    public function setDatasetcreated($datasetcreated)
    {
        $this->datasetcreated = $datasetcreated;

        return $this;
    }

    /**
     * Get datasetcreated
     *
     * @return integer
     */
    public function getDatasetcreated()
    {
        return $this->datasetcreated;
    }

    /**
     * Set datsetfileppath
     *
     * @param string $datsetfileppath
     * @return Dataset
     */
    public function setDatsetfileppath($datsetfileppath)
    {
        $this->datsetfileppath = $datsetfileppath;

        return $this;
    }

    /**
     * Get datsetfileppath
     *
     * @return string
     */
    public function getDatsetfileppath()
    {
        return $this->datsetfileppath;
    }

    /**
     * Set datasetupdated
     *
     * @param integer $datasetupdated
     * @return Dataset
     */
    public function setDatasetupdated($datasetupdated)
    {
        $this->datasetupdated = $datasetupdated;

        return $this;
    }

    /**
     * Get datasetupdated
     *
     * @return integer
     */
    public function getDatasetupdated()
    {
        return $this->datasetupdated;
    }

    /**
     * Set datasetdescription
     *
     * @param string $datasetdescription
     * @return Dataset
     */
    public function setDatasetdescription($datasetdescription)
    {
        $this->datasetdescription = $datasetdescription;

        return $this;
    }

    /**
     * Get datasetdescription
     *
     * @return string
     */
    public function getDatasetdescription()
    {
        return $this->datasetdescription;
    }

    /**
     * Get datasetid
     *
     * @return integer
     */
    public function getDatasetid()
    {
        return $this->datasetid;
    }

    /**
     * Set userid
     *
     * @param \Base\UserBundle\Entity\User $userid
     * @return Dataset
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
