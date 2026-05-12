<?php

declare(strict_types=1);

namespace App\Domain\Access\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'uniq_user_sso_subject', columns: ['sso_subject'])]
#[ORM\HasLifecycleCallbacks]
#[Assert\Callback('validateAuthenticationConsistency')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use IdentifierTrait;
    use TimestampableTrait;

    public const TYPE_ADMIN_EXTERNAL = 'admin_external';
    public const TYPE_ADMIN_SSO = 'admin_sso';
    public const TYPE_AGENT_SSO = 'agent_sso';
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_EXTERNAL_PROFESSIONAL = self::TYPE_EXTERNAL;

    public const AUTH_PROVIDER_LOCAL = 'local';
    public const AUTH_PROVIDER_SSO = 'sso';

    public const TYPE_LABELS = [
        self::TYPE_ADMIN_EXTERNAL => 'Administrateur (ext)',
        self::TYPE_ADMIN_SSO => 'Administrateur SSO',
        self::TYPE_AGENT_SSO => 'Agent SSO',
        self::TYPE_EXTERNAL => 'Externe',
    ];

    private const LEGACY_TYPE_MAP = [
        'admin' => self::TYPE_ADMIN_EXTERNAL,
        'agent' => self::TYPE_AGENT_SSO,
        'external_professional' => self::TYPE_EXTERNAL,
        'external_company' => self::TYPE_EXTERNAL,
        'external_partner' => self::TYPE_EXTERNAL,
    ];

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $email = '';

    #[ORM\Column(type: Types::STRING)]
    private string $password = '';

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $displayName = '';

    #[ORM\Column(type: Types::STRING, length: 40, options: ['default' => self::TYPE_EXTERNAL])]
    private string $userType = self::TYPE_EXTERNAL;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $organizationName = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    private ?string $websiteUrl = null;

    #[ORM\Column(type: Types::STRING, length: 16, options: ['default' => self::AUTH_PROVIDER_LOCAL])]
    private string $authProvider = self::AUTH_PROVIDER_LOCAL;

    #[ORM\Column(type: Types::STRING, length: 190, nullable: true)]
    private ?string $ssoSubject = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    /** @var Collection<int, Role> */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_role')]
    private Collection $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getUserType(): string
    {
        return self::normalizeUserType($this->userType);
    }

    public function setUserType(string $userType): static
    {
        $this->userType = self::normalizeUserType($userType);

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName !== null ? trim($firstName) : null;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName !== null ? trim($lastName) : null;

        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(?string $organizationName): static
    {
        $this->organizationName = $organizationName !== null ? trim($organizationName) : null;

        return $this;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): static
    {
        $this->websiteUrl = $websiteUrl !== null ? trim($websiteUrl) : null;

        return $this;
    }

    public function getAuthProvider(): string
    {
        return $this->authProvider;
    }

    public function setAuthProvider(string $authProvider): static
    {
        $this->authProvider = strtolower(trim($authProvider));

        return $this;
    }

    public function getSsoSubject(): ?string
    {
        return $this->ssoSubject;
    }

    public function setSsoSubject(?string $ssoSubject): static
    {
        $this->ssoSubject = $ssoSubject !== null ? trim($ssoSubject) : null;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return array<int, string> */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        foreach ($this->roles as $role) {
            $roles[] = $role->getCode();
        }

        return array_values(array_unique($roles));
    }

    /** @return Collection<int, Role> */
    public function getRoleEntities(): Collection
    {
        return $this->roles;
    }

    /**
     * @param iterable<Role> $roleEntities
     */
    public function setRoleEntities(iterable $roleEntities): static
    {
        $this->roles->clear();
        foreach ($roleEntities as $role) {
            if ($role instanceof Role) {
                $this->addRole($role);
            }
        }

        return $this;
    }

    public function addRoleEntity(Role $role): static
    {
        return $this->addRole($role);
    }

    public function removeRoleEntity(Role $role): static
    {
        return $this->removeRole($role);
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        $this->roles->removeElement($role);

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getTypeLabel(): string
    {
        $userType = $this->getUserType();

        return self::TYPE_LABELS[$userType] ?? $userType;
    }

    public function isExternalUser(): bool
    {
        return \in_array($this->getUserType(), [
            self::TYPE_EXTERNAL,
            self::TYPE_ADMIN_EXTERNAL,
        ], true);
    }

    /**
     * @return list<string>
     */
    public static function localAccountTypes(): array
    {
        return [
            self::TYPE_ADMIN_EXTERNAL,
            self::TYPE_EXTERNAL,
        ];
    }

    /**
     * @return list<string>
     */
    public static function ssoAccountTypes(): array
    {
        return [
            self::TYPE_ADMIN_SSO,
            self::TYPE_AGENT_SSO,
        ];
    }

    public function isSsoAccountType(): bool
    {
        return \in_array($this->getUserType(), self::ssoAccountTypes(), true);
    }

    public static function expectedAuthProviderForType(string $userType): string
    {
        return \in_array(self::normalizeUserType($userType), self::ssoAccountTypes(), true)
            ? self::AUTH_PROVIDER_SSO
            : self::AUTH_PROVIDER_LOCAL;
    }

    public function validateAuthenticationConsistency(ExecutionContextInterface $context): void
    {
        if ($this->isSsoAccountType() && trim((string) $this->ssoSubject) === '') {
            $context->buildViolation('Identifiant SSO obligatoire pour un compte SSO.')
                ->atPath('ssoSubject')
                ->addViolation();
        }
    }

    private static function normalizeUserType(string $userType): string
    {
        $normalized = strtolower(trim($userType));
        if ($normalized === '') {
            return self::TYPE_EXTERNAL;
        }

        if (isset(self::LEGACY_TYPE_MAP[$normalized])) {
            return self::LEGACY_TYPE_MAP[$normalized];
        }

        return match ($normalized) {
            self::TYPE_ADMIN_EXTERNAL,
            self::TYPE_ADMIN_SSO,
            self::TYPE_AGENT_SSO,
            self::TYPE_EXTERNAL => $normalized,
            default => self::TYPE_EXTERNAL,
        };
    }

    public function __toString(): string
    {
        return $this->displayName !== '' ? $this->displayName : $this->email;
    }
}
