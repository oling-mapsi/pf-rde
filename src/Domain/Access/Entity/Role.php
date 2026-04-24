<?php

declare(strict_types=1);

namespace App\Domain\Access\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'app_role')]
#[ORM\UniqueConstraint(name: 'uniq_role_code', columns: ['code'])]
#[ORM\HasLifecycleCallbacks]
class Role
{
    use IdentifierTrait;
    use TimestampableTrait;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $label;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $code;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'roles')]
    private Collection $users;

    public function __construct(string $label = '', string $code = 'ROLE_USER')
    {
        $this->label = $label;
        $this->code = $code;
        $this->users = new ArrayCollection();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper($code);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function __toString(): string
    {
        return $this->label !== '' ? $this->label : $this->code;
    }
}
