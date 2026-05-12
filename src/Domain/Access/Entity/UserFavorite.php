<?php

declare(strict_types=1);

namespace App\Domain\Access\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\UserFavoriteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFavoriteRepository::class)]
#[ORM\Table(name: 'user_favorite')]
#[ORM\UniqueConstraint(name: 'uniq_user_favorite', columns: ['user_id', 'resource_kind', 'resource_slug'])]
#[ORM\Index(name: 'idx_user_favorite_user', columns: ['user_id'])]
#[ORM\HasLifecycleCallbacks]
class UserFavorite
{
    use IdentifierTrait;
    use TimestampableTrait;

    public const KIND_DATA_SOURCE = 'data_source';
    public const KIND_STATIC_MAP = 'static_map';
    public const KIND_INTERACTIVE_MAP = 'interactive_map';

    public const KIND_LABELS = [
        self::KIND_DATA_SOURCE => 'Source de données',
        self::KIND_STATIC_MAP => 'Carte statique',
        self::KIND_INTERACTIVE_MAP => 'Carte interactive',
    ];

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $resourceKind = self::KIND_DATA_SOURCE;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $resourceSlug = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $resourceTitle = '';

    #[ORM\Column(type: Types::STRING, length: 512)]
    private string $resourceUrl = '';

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getResourceKind(): string
    {
        return $this->resourceKind;
    }

    public function setResourceKind(string $resourceKind): static
    {
        $this->resourceKind = strtolower(trim($resourceKind));

        return $this;
    }

    public function getResourceSlug(): string
    {
        return $this->resourceSlug;
    }

    public function setResourceSlug(string $resourceSlug): static
    {
        $this->resourceSlug = trim($resourceSlug);

        return $this;
    }

    public function getResourceTitle(): string
    {
        return $this->resourceTitle;
    }

    public function setResourceTitle(string $resourceTitle): static
    {
        $this->resourceTitle = trim($resourceTitle);

        return $this;
    }

    public function getResourceUrl(): string
    {
        return $this->resourceUrl;
    }

    public function setResourceUrl(string $resourceUrl): static
    {
        $this->resourceUrl = trim($resourceUrl);

        return $this;
    }

    public function getKindLabel(): string
    {
        return self::KIND_LABELS[$this->resourceKind] ?? $this->resourceKind;
    }
}

