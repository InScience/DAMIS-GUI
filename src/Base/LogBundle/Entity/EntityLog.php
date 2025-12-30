<?php

namespace Base\LogBundle\Entity;

use Base\LogBundle\Entity\EntityLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * Page Log
 *
 * NOTE: This entity was formerly a child of Gedmo\Loggable\Entity\LogEntry.
 * The Gedmo extensions were removed during the Symfony 3.4 upgrade.
 * This is now a standalone entity with the required properties added directly.
 */
#[ORM\Table(name: 'entity_log')]
#[ORM\Entity(repositoryClass: EntityLogRepository::class)]
class EntityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 8)]
    private ?string $action = null;

    #[ORM\Column(name: 'logged_at', type: Types::DATETIME_MUTABLE)]
    private ?DateTime $loggedAt = null;

    #[ORM\Column(name: 'object_id', type: Types::STRING, length: 64, nullable: true)]
    private ?string $objectId = null;

    #[ORM\Column(name: 'object_class', type: Types::STRING, length: 255)]
    private ?string $objectClass = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $version = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = [];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $username = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getLoggedAt(): ?DateTime
    {
        return $this->loggedAt;
    }

    public function setLoggedAt(DateTime $loggedAt): self
    {
        $this->loggedAt = $loggedAt;
        return $this;
    }

    public function getObjectId(): ?string
    {
        return $this->objectId;
    }

    public function setObjectId(?string $objectId): self
    {
        $this->objectId = $objectId;
        return $this;
    }

    public function getObjectClass(): ?string
    {
        return $this->objectClass;
    }

    public function setObjectClass(string $objectClass): self
    {
        $this->objectClass = $objectClass;
        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }
}