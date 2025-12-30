<?php

namespace Damis\DatasetsBundle\Entity;

use Damis\DatasetsBundle\Entity\Repository\DatasetRepository;
use Base\UserBundle\Entity\User;
use Damis\DatasetsBundle\Form\Validators as Asserts;
use Doctrine\ORM\Mapping as ORM;
// use Iphp\FileStoreBundle\Mapping\Annotation as FileStore;
/**
 * Dataset
 */
#[ORM\Table(name: 'dataset')]
#[ORM\Index(name: 'FK_DATASET_DAMISUSER', columns: ['UserID'])]
#[ORM\UniqueConstraint(name: 'DATASET_PK', columns: ['DatasetID'])]
#[ORM\Entity(repositoryClass: DatasetRepository::class)]
class Dataset
{
    private $seed = 'dcmaga7v5udgyhj0lwen';

    /**
     * @var integer
     */
    #[ORM\Column(name: 'DatasetIsMIDAS', type: 'integer', nullable: false)]
    private $datasetIsMidas;

    /**
     * @var string
     */
    #[ORM\Column(name: 'DatasetTitle', type: 'string', length: 80, nullable: false)]
    private $datasetTitle;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'DatasetCreated', type: 'integer', nullable: false)]
    private $datasetCreated;

    /**
     * @var string
     */
    #[ORM\Column(name: 'DatasetFilePath', type: 'string', length: 255, nullable: true)]
    private $filePath;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'DatasetUpdated', type: 'integer', nullable: true)]
    private $datasetUpdated;

    /**
     * File upload property - needs new implementation for SF4.4
     * 
     * @var mixed
     */
    private $file;

    /**
     * @var string
     */
    #[ORM\Column(name: 'DatasetDescription', type: 'string', length: 500, nullable: true)]
    private $datasetDescription;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'DatasetID', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $datasetId;

    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'UserID', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'Hidden', type: 'integer', nullable: true)]
    private $hidden = 0;

    /**
     * @param int $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * @return int
     */
    public function getHidden()
    {
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
     * Set user
     *
     * @param User $user
     * @return Dataset
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
     * Create md5 secured with seed and user id string
     *
     * @return string
     */
    public function getUserIdMd5()
    {
        return md5($this->seed.$this->user);
    }

    /**
     * Create md5 secured with seed and user id string + dataset subatalog
     *
     * @return string
     */
    public function getUserIdMd5Dataset()
    {
        if ($this->hidden) {
            return $this->getUserIdMd5()."/experiment";
        }
        return $this->getUserIdMd5()."/dataset";
    }

    /**
     * Create md5 secured with seed and file id string
     *
     * @return string
     */
    public function getDatasetIdMd5()
    {
        return md5($this->datasetId.$this->seed);
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
        if ($this->file === null) {
            $size = 0;
            // Try to calculate size from filePath
            if ($this->filePath) {
                 $projectRoot = realpath(__DIR__ . '/../../../../'); 
                 $fullPath = $projectRoot . '/public' . $this->filePath;
                 if (file_exists($fullPath)) {
                     $size = filesize($fullPath);
                 }
            }
            return ['size' => $size];
        }
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

    public function getId()
    {
        return $this->datasetId;
    }
}