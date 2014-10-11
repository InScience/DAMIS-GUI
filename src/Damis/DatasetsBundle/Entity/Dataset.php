<?php

namespace Damis\DatasetsBundle\Entity;

use Damis\DatasetsBundle\Form\Validators as Asserts;
use Doctrine\ORM\Mapping as ORM;
use Iphp\FileStoreBundle\Mapping\Annotation as FileStore;

/**
 * Dataset
 *
 * @ORM\Table(name="dataset", uniqueConstraints={@ORM\UniqueConstraint(name="DATASET_PK", columns={"DatasetID"})}, indexes={@ORM\Index(name="FK_DATASET_DAMISUSER", columns={"UserID"})})
 * @FileStore\Uploadable*
 * @ORM\Entity(repositoryClass="Damis\DatasetsBundle\Entity\Repository\DatasetRepository")
 */
class Dataset
{
    private $seed = 'dcmaga7v5udgyhj0lwen';

    /**
     * @var integer
     *
     * @ORM\Column(name="DatasetIsMIDAS", type="integer", nullable=false)
     */
    private $datasetIsMidas;

    /**
     * @var string
     *
     * @ORM\Column(name="DatasetTitle", type="string", length=80, nullable=false)
     */
    private $datasetTitle;

    /**
     * @var integer
     *
     * @ORM\Column(name="DatasetCreated", type="integer", nullable=false)
     */
    private $datasetCreated;

    /**
     * @var string
     *
     * @ORM\Column(name="DatasetFilePath", type="string", length=255, nullable=true)
     */
    private $filePath;

    /**
     * @var integer
     *
     * @ORM\Column(name="DatasetUpdated", type="integer", nullable=true)
     */
    private $datasetUpdated;

    /**
     * @var array
     * @Asserts\FileExtension
     * @ORM\Column(name="file", type="array", nullable=true)
     * @FileStore\UploadableField(mapping="dataset")
     */
    private $file;

    /**
     * @var string
     *
     * @ORM\Column(name="DatasetDescription", type="string", length=500, nullable=true)
     */
    private $datasetDescription;

    /**
     * @var integer
     *
     * @ORM\Column(name="DatasetID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $datasetId;

    /**
     * @var \Base\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Base\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="id")
     * })
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Hidden", type="integer", nullable=true)
     */
    private $hidden = 0;

    /**
     * @param int $hidden
     */
    public function setHidden($hidden) {
        $this->hidden = $hidden;
    }

    /**
     * @return int
     */
    public function getHidden() {
        return $this->hidden;
    }

    /**
     * Set datasetIsMidas
     *
     * @param integer $datasetIsMidas
     * @return Dataset
     */
    public function setDatasetIsMidas($datasetIsMidas)
    {
        $this->datasetIsMidas = $datasetIsMidas;

        return $this;
    }

    /**
     * Get datasetIsMidas
     *
     * @return integer
     */
    public function getDatasetIsMidas()
    {
        return $this->datasetIsMidas;
    }

    /**
     * Set datasetTitle
     *
     * @param string $datasetTitle
     * @return Dataset
     */
    public function setDatasetTitle($datasetTitle)
    {
        // Remove spaces in title, to fit arff format
        $datasetTitle = preg_replace('/\s+/', '_', $datasetTitle);
        $this->datasetTitle = $datasetTitle;

        return $this;
    }

    /**
     * Get datasetTitle
     *
     * @return string
     */
    public function getDatasetTitle()
    {
        return $this->datasetTitle;
    }

    /**
     * Set datasetCreated
     *
     * @param integer $datasetCreated
     * @return Dataset
     */
    public function setDatasetCreated($datasetCreated)
    {
        $this->datasetCreated = $datasetCreated;

        return $this;
    }

    /**
     * Get datasetCreated
     *
     * @return integer
     */
    public function getDatasetCreated()
    {
        return $this->datasetCreated;
    }

    /**
     * Set datasetUpdated
     *
     * @param integer $datasetUpdated
     * @return Dataset
     */
    public function setDatasetUpdated($datasetUpdated)
    {
        $this->datasetUpdated = $datasetUpdated;

        return $this;
    }

    /**
     * Get datasetUpdated
     *
     * @return integer
     */
    public function getDatasetUpdated()
    {
        return $this->datasetUpdated;
    }

    /**
     * Set datasetDescription
     *
     * @param string $datasetDescription
     * @return Dataset
     */
    public function setDatasetDescription($datasetDescription)
    {
        $this->datasetDescription = $datasetDescription;

        return $this;
    }

    /**
     * Get datasetDescription
     *
     * @return string
     */
    public function getDatasetDescription()
    {
        return $this->datasetDescription;
    }

    /**
     * Get datasetId
     *
     * @return integer
     */
    public function getDatasetId()
    {
        return $this->datasetId;
    }

    /**
     * Set userId
     *
     * @param \Base\UserBundle\Entity\User $userId
     * @return Dataset
     */
    public function setUserId(\Base\UserBundle\Entity\User $userId = null)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return \Base\UserBundle\Entity\User
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Create md5 secured with seed and user id string
     *
     * @return string
     */
    public function getUserIdMd5(){
        return md5($this->seed . $this->userId);
    }

    /**
     * Create md5 secured with seed and user id string + dataset subatalog
     *
     * @return string
     */
    public function getUserIdMd5Dataset(){
        if ($this->hidden) return $this->getUserIdMd5()."/experiment";
        return $this->getUserIdMd5()."/dataset";
    }

    /**
     * Create md5 secured with seed and file id string
     *
     * @return string
     */
    public function getDatasetIdMd5(){
        return md5($this->datasetId . $this->seed);
    }

    /**
     * Set file
     *
     * @param array $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Get file
     *
     * @return array
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set dataset arff file path
     *
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Get dataset arff file path
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

}
