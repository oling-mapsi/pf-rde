<?php

declare(strict_types=1);

namespace App\Domain\Common\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeletableTrait
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function markDeleted(?\DateTimeImmutable $deletedAt = null): static
    {
        $this->deletedAt = $deletedAt ?? new \DateTimeImmutable();

        return $this;
    }
}
