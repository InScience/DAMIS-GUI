<?php

namespace Base\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Base\MainBundle\Repository\CronJobRepository;

#[ORM\Entity(repositoryClass: CronJobRepository::class)]
#[ORM\Table(name: "cron_job")]
class CronJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: "string", length: 500)]
    #[Assert\NotBlank]
    private ?string $command = null;

    #[ORM\Column(type: "string", length: 20)]
    #[Assert\NotBlank]
    private ?string $schedule = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $logFile = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $errorFile = null;

    #[ORM\Column(type: "boolean")]
    private bool $enabled = true;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $lastRun = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(string $command): self
    {
        $this->command = $command;
        return $this;
    }

    public function getSchedule(): ?string
    {
        return $this->schedule;
    }

    public function setSchedule(string $schedule): self
    {
        $this->schedule = $schedule;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    public function setLogFile(?string $logFile): self
    {
        $this->logFile = $logFile;
        return $this;
    }

    public function getErrorFile(): ?string
    {
        return $this->errorFile;
    }

    public function setErrorFile(?string $errorFile): self
    {
        $this->errorFile = $errorFile;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastRun(): ?\DateTimeInterface
    {
        return $this->lastRun;
    }

    public function setLastRun(?\DateTimeInterface $lastRun): self
    {
        $this->lastRun = $lastRun;
        return $this;
    }

    /**
     * Get the full cron expression
     */
    public function getFullCronExpression(): string
    {
        $command = $this->command;
        
        // Add output redirection if log files are specified
        if ($this->logFile) {
            $command .= ' >> ' . $this->logFile;
        }
        
        if ($this->errorFile) {
            $command .= ' 2>> ' . $this->errorFile;
        }
        
        return $this->schedule . ' ' . $command;
    }
}