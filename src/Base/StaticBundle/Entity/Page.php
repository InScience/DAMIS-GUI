<?php

namespace Base\StaticBundle\Entity;

use Base\StaticBundle\Entity\PageRepository; // Added this
use Base\StaticBundle\Entity\LanguageEnum; // Added this
use Doctrine\DBAL\Types\Types; // Added this
use Doctrine\ORM\Mapping as ORM;
use DateTime; // Changed from \DateTime

#[ORM\Table(name: 'page')]
#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(name: 'groupName', type: Types::STRING, length: 255, nullable: true)]
    private ?string $groupName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $position = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $updated = null;

    // THIS IS THE MODERN WAY TO HANDLE ENUMS
    #[ORM\Column(name: 'language', enumType: LanguageEnum::class)]
    private ?LanguageEnum $language = null;

    public function __construct()
    {
        $this->created = new DateTime();
        $this->updated = new DateTime();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated = new DateTime();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): void
    {
        if (null === $this->title) {
            return;
        }
        $slug = strtolower($this->title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $this->slug = $slug;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(?string $groupName): self
    {
        $this->groupName = $groupName;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    public function setUpdated(DateTime $updated): self
    {
        $this->updated = $updated;
        return $this;
    }

    public function getLanguage(): ?LanguageEnum
    {
        return $this->language;
    }

    public function setLanguage(?LanguageEnum $language): self
    {
        $this->language = $language;
        return $this;
    }
}