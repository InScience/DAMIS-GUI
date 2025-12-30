<?php

namespace Damis\AlgorithmBundle\Entity;

use Damis\AlgorithmBundle\Entity\Repository\FileRepository;
use Base\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Damis\AlgorithmBundle\Form\Validators as Asserts;

/**
 * File
 */
#[ORM\Table(name: 'useralgorithm')]
#[ORM\Index(name: 'FK_ALGORITHM_FILE_DAMISUSER', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'USER_ALGORITHM_FILE_PK', columns: ['id'])]
#[ORM\Entity(repositoryClass: FileRepository::class)]
class File
{
    private $seed = 'dcmaga7v5udgyhj0lwen';

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $fileId;

    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'file_title', type: 'string', length: 80, nullable: false)]
    private $fileTitle;
    
    /**
     * @var integer
     */
    #[ORM\Column(name: 'file_created', type: 'integer', nullable: false)]
    private $fileCreated;

    /**
     * @var string
     */
    #[ORM\Column(name: 'file_path', type: 'string', length: 255, nullable: true)]
    private $filePath;
    
    /**
     * @var integer
     */
    #[ORM\Column(name: 'file_updated', type: 'integer', nullable: true)]
    private $fileUpdated;

    /**
     * NOT persisted to database - only used during form upload
     *
     * @Asserts\FileExtension
     */
    #[Assert\File(maxSize: '20M')]
    // #[Assert\NotBlank(groups: ['create'])]
    private $file;

    /**
     * @var string
     */
    #[ORM\Column(name: 'file_description', type: 'string', length: 500, nullable: true)]
    private $fileDescription;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'hidden', type: 'integer', nullable: true)]
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
     * Set fileTitle
     *
     * @param string $fileTitle
     * @return File
     */
    public function setFileTitle($fileTitle)
    {
        $fileTitle = preg_replace('/\s+/', '_', $fileTitle);
        $this->fileTitle = $fileTitle;
        return $this;
    }

    /**
     * Get fileTitle
     *
     * @return string
     */
    public function getFileTitle()
    {
        // Convert underscores to spaces for display purposes
        return str_replace('_', ' ', (string) $this->fileTitle);
    }
    
    /**
     * Set fileCreated
     *
     * @param integer $fileCreated
     * @return File
     */
    public function setFileCreated($fileCreated)
    {
        $this->fileCreated = $fileCreated;
        return $this;
    }

    /**
     * Get fileCreated
     *
     * @return integer
     */
    public function getFileCreated()
    {
        return $this->fileCreated;
    }

    /**
     * Set fileUpdated
     *
     * @param integer $fileUpdated
     * @return File
     */
    public function setFileUpdated($fileUpdated)
    {
        $this->fileUpdated = $fileUpdated;
        return $this;
    }

    /**
     * Get fileUpdated
     *
     * @return integer
     */
    public function getFileUpdated()
    {
        return $this->fileUpdated;
    }

    /**
     * Set fileDescription
     *
     * @param string $fileDescription
     * @return File
     */
    public function setFileDescription($fileDescription)
    {
        $this->fileDescription = $fileDescription;
        return $this;
    }

    /**
     * Get fileDescription
     *
     * @return string
     */
    public function getFileDescription()
    {
        return $this->fileDescription;
    }

    /**
     * Get fileId
     *
     * @return integer
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return File
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
        return md5($this->seed.$this->user->getId());
    }

    /**
     * Create md5 secured with seed and user id string + files subcatalog
     *
     * @return string
     */
    public function getUserIdMd5File()
    {
        if ($this->hidden) {
            return $this->getUserIdMd5()."/hidden";
        }
        return $this->getUserIdMd5()."/algorithms";
    }

    /**
     * Create md5 secured with seed and file id string
     *
     * @return string
     */
    public function getFileIdMd5()
    {
        return md5($this->fileId.$this->seed);
    }

    /**
     * Set file (transient property - not persisted)
     */
    public function setFile(mixed $file)
    {
        $this->file = $file;
    }

    /**
     * Get file (transient property - not persisted)
     *
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }
    
    /**
     * Set original file path
     *
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Get original file path
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }
}