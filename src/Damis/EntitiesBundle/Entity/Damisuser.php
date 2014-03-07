<?php

namespace Damis\EntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Damisuser
 *
 * @ORM\Table(name="damisuser", uniqueConstraints={@ORM\UniqueConstraint(name="DAMISUSER_PK", columns={"UserID"})})
 * @ORM\Entity
 */
class Damisuser
{
    /**
     * @var string
     *
     * @ORM\Column(name="UserName", type="string", length=80, nullable=false)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="UserPassword", type="string", length=80, nullable=false)
     */
    private $userpassword;

    /**
     * @var integer
     *
     * @ORM\Column(name="UserLastLogIn", type="integer", nullable=false)
     */
    private $userlastlogin;

    /**
     * @var string
     *
     * @ORM\Column(name="UserFirstName", type="string", length=80, nullable=false)
     */
    private $userfirstname;

    /**
     * @var string
     *
     * @ORM\Column(name="UserLastName", type="string", length=80, nullable=false)
     */
    private $userlastname;

    /**
     * @var string
     *
     * @ORM\Column(name="UserEmail", type="string", length=80, nullable=false)
     */
    private $useremail;

    /**
     * @var integer
     *
     * @ORM\Column(name="UserIsSuperuser", type="integer", nullable=false)
     */
    private $userissuperuser;

    /**
     * @var integer
     *
     * @ORM\Column(name="UserDateJoined", type="integer", nullable=false)
     */
    private $userdatejoined;

    /**
     * @var integer
     *
     * @ORM\Column(name="UserIsActive", type="integer", nullable=false)
     */
    private $userisactive;

    /**
     * @var integer
     *
     * @ORM\Column(name="UserIsStaff", type="integer", nullable=false)
     */
    private $userisstaff;

    /**
     * @var integer
     *
     * @ORM\Column(name="UserID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userid;



    /**
     * Set username
     *
     * @param string $username
     * @return Damisuser
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set userpassword
     *
     * @param string $userpassword
     * @return Damisuser
     */
    public function setUserpassword($userpassword)
    {
        $this->userpassword = $userpassword;

        return $this;
    }

    /**
     * Get userpassword
     *
     * @return string 
     */
    public function getUserpassword()
    {
        return $this->userpassword;
    }

    /**
     * Set userlastlogin
     *
     * @param integer $userlastlogin
     * @return Damisuser
     */
    public function setUserlastlogin($userlastlogin)
    {
        $this->userlastlogin = $userlastlogin;

        return $this;
    }

    /**
     * Get userlastlogin
     *
     * @return integer 
     */
    public function getUserlastlogin()
    {
        return $this->userlastlogin;
    }

    /**
     * Set userfirstname
     *
     * @param string $userfirstname
     * @return Damisuser
     */
    public function setUserfirstname($userfirstname)
    {
        $this->userfirstname = $userfirstname;

        return $this;
    }

    /**
     * Get userfirstname
     *
     * @return string 
     */
    public function getUserfirstname()
    {
        return $this->userfirstname;
    }

    /**
     * Set userlastname
     *
     * @param string $userlastname
     * @return Damisuser
     */
    public function setUserlastname($userlastname)
    {
        $this->userlastname = $userlastname;

        return $this;
    }

    /**
     * Get userlastname
     *
     * @return string 
     */
    public function getUserlastname()
    {
        return $this->userlastname;
    }

    /**
     * Set useremail
     *
     * @param string $useremail
     * @return Damisuser
     */
    public function setUseremail($useremail)
    {
        $this->useremail = $useremail;

        return $this;
    }

    /**
     * Get useremail
     *
     * @return string 
     */
    public function getUseremail()
    {
        return $this->useremail;
    }

    /**
     * Set userissuperuser
     *
     * @param integer $userissuperuser
     * @return Damisuser
     */
    public function setUserissuperuser($userissuperuser)
    {
        $this->userissuperuser = $userissuperuser;

        return $this;
    }

    /**
     * Get userissuperuser
     *
     * @return integer 
     */
    public function getUserissuperuser()
    {
        return $this->userissuperuser;
    }

    /**
     * Set userdatejoined
     *
     * @param integer $userdatejoined
     * @return Damisuser
     */
    public function setUserdatejoined($userdatejoined)
    {
        $this->userdatejoined = $userdatejoined;

        return $this;
    }

    /**
     * Get userdatejoined
     *
     * @return integer 
     */
    public function getUserdatejoined()
    {
        return $this->userdatejoined;
    }

    /**
     * Set userisactive
     *
     * @param integer $userisactive
     * @return Damisuser
     */
    public function setUserisactive($userisactive)
    {
        $this->userisactive = $userisactive;

        return $this;
    }

    /**
     * Get userisactive
     *
     * @return integer 
     */
    public function getUserisactive()
    {
        return $this->userisactive;
    }

    /**
     * Set userisstaff
     *
     * @param integer $userisstaff
     * @return Damisuser
     */
    public function setUserisstaff($userisstaff)
    {
        $this->userisstaff = $userisstaff;

        return $this;
    }

    /**
     * Get userisstaff
     *
     * @return integer 
     */
    public function getUserisstaff()
    {
        return $this->userisstaff;
    }

    /**
     * Get userid
     *
     * @return integer 
     */
    public function getUserid()
    {
        return $this->userid;
    }
}
