<?php

namespace Base\StaticBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Page
 *
 * @ORM\Table(name="page")
 * @ORM\Entity(repositoryClass="Base\StaticBundle\Entity\PageRepository")
 * @Gedmo\Loggable(logEntryClass="Base\LogBundle\Entity\EntityLog")
 */
class Page
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @Gedmo\Slug(fields={"title"}, updatable=true)
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="group", type="string", length=255)
     */
    private $group;

    /**
     * @Gedmo\Slug(fields={"group"}, updatable=true)
     * @ORM\Column(length=128, unique=true)
     */
    private $groupSlug;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    private $text;

    /**
     * @var integer
     * @Gedmo\Versioned
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Page
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Page
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return Page
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return Page
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $group
     */
    public function setGroup($group) {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param mixed $groupSlug
     */
    public function setGroupSlug($groupSlug) {
        $this->groupSlug = $groupSlug;
    }

    /**
     * @return mixed
     */
    public function getGroupSlug() {
        return $this->groupSlug;
    }

}
