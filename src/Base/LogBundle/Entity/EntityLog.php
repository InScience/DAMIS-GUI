<?php

namespace Base\LogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Loggable\Entity\LogEntry as BaseEntity;

/**
 * Page Log
 *
 * @ORM\Table(name="entity_log")
 * @ORM\Entity(repositoryClass="Base\LogBundle\Entity\EntityLogRepository")
 */
class EntityLog extends BaseEntity
{
}
