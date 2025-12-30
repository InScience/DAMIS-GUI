<?php

namespace Base\UserBundle\Entity;

// Commented out the old import for FOS\UserBundle\Entity\User during the upgrade to Symfony 3.4
// use FOS\UserBundle\Entity\User as BaseUser;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Base\UserBundle\Entity\UserRepository;


#[ORM\Table(name: 'users')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends BaseUser
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\Column(name: 'locked', type: Types::BOOLEAN)]
    protected $locked = false;

    #[ORM\Column(name: 'expired', type: Types::BOOLEAN)]
    protected $expired = false;

    #[ORM\Column(name: 'credentials_expired', type: Types::BOOLEAN)]
    protected $credentialsExpired = false;

    #[ORM\Column(name: 'registeredAt', type: Types::DATETIME_MUTABLE)]
    protected \DateTime $registeredAt;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: true)]
    protected $name = null;

    #[ORM\Column(name: 'surname', type: Types::STRING, length: 255, nullable: true)]
    protected $surname = null;

    #[ORM\Column(name: 'organisation', type: Types::STRING, length: 255, nullable: true)]
    protected $organisation = null;

    #[ORM\Column(name: 'user_id', type: Types::STRING, length: 255, nullable: true)]
    protected $userId;

    /**
     * Set locked
     *
     * @param boolean $locked
     * @return User
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;
        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Check if user is locked
     *
     * @return boolean
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * Set expired
     *
     * @param boolean $expired
     * @return User
     */
    public function setExpired($expired)
    {
        $this->expired = $expired;
        return $this;
    }

    /**
     * Get expired
     *
     * @return boolean
     */
    public function getExpired()
    {
        return $this->expired;
    }

    /**
     * Check if user is expired
     *
     * @return boolean
     */
    public function isExpired()
    {
        return $this->expired;
    }

    /**
     * Set credentialsExpired
     *
     * @param boolean $credentialsExpired
     * @return User
     */
    public function setCredentialsExpired($credentialsExpired)
    {
        $this->credentialsExpired = $credentialsExpired;
        return $this;
    }

    /**
     * Get credentialsExpired
     *
     * @return boolean
     */
    public function getCredentialsExpired()
    {
        return $this->credentialsExpired;
    }

    /**
     * Check if credentials are expired
     *
     * @return boolean
     */
    public function isCredentialsExpired()
    {
        return $this->credentialsExpired;
    }

    /**
     * Set registeredAt
     *
     * @param \DateTime $registeredAt
     * @return User
     */
    public function setRegisteredAt($registeredAt = null)
    {
        if ($registeredAt === null) {
            $registeredAt = new \DateTime("now");
        }
        $this->registeredAt = $registeredAt;
        return $this;
    }

    /**
     * Get registeredAt
     *
     * @return \DateTime
     */
    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return User
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
     * Set surname
     *
     * @param string $surname
     * @return User
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;
        return $this;
    }

    /**
     * Get surname
     *
     * @return string
     */
    public function getSurname()
    {
        return $this->surname;
    }

    public function __construct()
    {
        parent::__construct();
        $this->registeredAt = new \DateTime("now");
        $this->salt = '';
    }

    public function setEmail($email)
    {
        $this->email = $email;
        parent::setEmail($email);
    }

    public function setUsername($name)
    {
        $this->username = $name;
        parent::setUsername($name);
    }

    /**
     * Get Name and Surname
     *
     * @return string
     */
    public function getNameSurname()
    {
        return trim($this->getName().' '.$this->getSurname());
    }

    /**
     * Get Surname and Name
     *
     * @return string
     */
    public function getSurnameName()
    {
        return trim($this->getSurname().' '.$this->getName());
    }

    /**
     * Set user name and surname from string
     *
     * @param $nameSurname
     * @return User
     */
    public function setNameSurname($nameSurname)
    {
        $nameSurname = trim($nameSurname);
        $pos = mb_stripos($nameSurname, ' ');

        if ($pos !== false) {
            $this->setName(mb_substr($nameSurname, 0, $pos));
            $this->setSurname(mb_substr($nameSurname, $pos+1));
        } else {
            $this->setName($nameSurname);
            $this->setSurname('');
        }

        return $this;
    }

    public function getPublicName()
    {
        if ($this->getNameSurname()) {
            return $this->getNameSurname();
        } else {
            return $this->getUsername();
        }
    }

    /**
     * Set organisation
     *
     * @param string $organisation
     * @return User
     */
    public function setOrganisation($organisation)
    {
        $this->organisation = $organisation;
        return $this;
    }

    /**
     * Get organisation
     *
     * @return string
     */
    public function getOrganisation()
    {
        return $this->organisation;
    }

    /**
     * Get userId
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set userId
     *
     * @param string $userId
     * @return User
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
}