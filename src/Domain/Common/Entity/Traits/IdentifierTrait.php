<?php

declare(strict_types=1);

namespace App\Domain\Common\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

trait IdentifierTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        if (!isset($this->uuid)) {
            $this->uuid = Uuid::v7();
        }

        return $this->uuid;
    }

    #[ORM\PrePersist]
    public function initializeUuid(): void
    {
        if (!isset($this->uuid)) {
            $this->uuid = Uuid::v7();
        }
    }
}
