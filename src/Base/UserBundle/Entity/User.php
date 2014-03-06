<?php

namespace Base\UserBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * User
 *
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="Base\UserBundle\Entity\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registeredAt", type="datetime")
     */
    protected $registeredAt;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    protected $name = null;

    /**
     * @var string
     *
     * @ORM\Column(name="surname", type="string", length=255, nullable=true)
     */
    protected $surname = null;

    /**
     * Set registeredAt
     *
     * @return User
     */
    public function setRegisteredAt()
    {
        $this->registeredAt = new \DateTime("now");

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
    }

    public function setEmail($email){
        parent::setEmail($email);
        parent::setUsername($email);

        if (empty($this->name)) $this->setName($this->getName());
    }

    public function setUsername($name){
        $this->username = $this->email;
    }

    /**
     * Get Name and Surname
     *
     * @return string
     */
    public function getNameSurname()
    {
        return trim($this->getName() . ' ' . $this->getSurname());;
    }

    /**
     * Get Surname and Name
     *
     * @return string
     */
    public function getSurnameName()
    {
        return trim($this->getSurname() . ' ' . $this->getName());;
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
        }
        else {
            $this->setName($nameSurname);
            $this->setSurname('');
        }

        return $this;
    }
}
