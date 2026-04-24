<?php

declare(strict_types=1);

namespace App\Domain\Common\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait MetadataTrait
{
    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    /** @return array<string, mixed>|null */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /** @param array<string, mixed>|null $metadata */
    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }
}
