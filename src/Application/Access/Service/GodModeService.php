<?php

declare(strict_types=1);

namespace App\Application\Access\Service;

use App\Domain\Access\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

final class GodModeService
{
    private const SESSION_PROFILE_KEY = 'god_mode.profile';
    private const PROFILE_PUBLIC = 'public_guest';
    private const PROFILE_EXTERNAL = 'external_professional';
    private const PROFILE_AGENT = 'agent_sso';
    private const PROFILE_MANAGER = 'manager_sso';
    private const PROFILE_ADMIN = 'admin_sso';

    /**
     * @var array<string, array{label: string, typeLabel: string, userType: ?string, roles: list<string>}>
     */
    private const PROFILES = [
        self::PROFILE_PUBLIC => [
            'label' => 'Utilisateur non connecté (simulation)',
            'typeLabel' => 'Public (non connecté)',
            'userType' => null,
            'roles' => [],
        ],
        self::PROFILE_EXTERNAL => [
            'label' => 'Utilisateur externe professionnel',
            'typeLabel' => 'Externe professionnel',
            'userType' => User::TYPE_EXTERNAL,
            'roles' => ['ROLE_EXTERNAL'],
        ],
        self::PROFILE_AGENT => [
            'label' => 'Agent SSO',
            'typeLabel' => 'Agent SSO',
            'userType' => User::TYPE_AGENT_SSO,
            'roles' => ['ROLE_AGENT'],
        ],
        self::PROFILE_MANAGER => [
            'label' => 'Gestionnaire SSO',
            'typeLabel' => 'Gestionnaire SSO',
            'userType' => User::TYPE_MANAGER_SSO,
            'roles' => ['ROLE_MANAGER'],
        ],
        self::PROFILE_ADMIN => [
            'label' => 'Administrateur SSO',
            'typeLabel' => 'Administrateur SSO',
            'userType' => User::TYPE_ADMIN_SSO,
            'roles' => ['ROLE_ADMIN'],
        ],
    ];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RoleHierarchyInterface $roleHierarchy,
        private readonly string $godModeEmail,
    ) {
    }

    /**
     * @return array<string, array{label: string, typeLabel: string, userType: ?string, roles: list<string>}>
     */
    public function getAvailableProfiles(): array
    {
        return self::PROFILES;
    }

    public function isEligible(?User $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (!\in_array('ROLE_GOD', $user->getRoles(), true)) {
            return false;
        }

        return strtolower(trim($user->getEmail())) === strtolower(trim($this->godModeEmail));
    }

    public function getCurrentProfileKey(?User $user): ?string
    {
        if (!$this->isEligible($user)) {
            return null;
        }

        $session = $this->requestStack->getSession();
        if ($session === null) {
            return null;
        }

        $value = trim((string) $session->get(self::SESSION_PROFILE_KEY, ''));
        if ($value === '' || !isset(self::PROFILES[$value])) {
            return null;
        }

        return $value;
    }

    public function setCurrentProfile(?User $user, string $profileKey): bool
    {
        if (!$this->isEligible($user)) {
            return false;
        }

        if (!isset(self::PROFILES[$profileKey])) {
            return false;
        }

        $session = $this->requestStack->getSession();
        if ($session === null) {
            return false;
        }

        $session->set(self::SESSION_PROFILE_KEY, $profileKey);

        return true;
    }

    public function clearCurrentProfile(?User $user): void
    {
        if (!$this->isEligible($user)) {
            return;
        }

        $session = $this->requestStack->getSession();
        if ($session === null) {
            return;
        }

        $session->remove(self::SESSION_PROFILE_KEY);
    }

    /**
     * @return list<string>
     */
    public function getEffectiveRoles(?User $user): array
    {
        if (!$user instanceof User) {
            return [];
        }

        if (!$this->isEligible($user)) {
            return array_values(array_unique($user->getRoles()));
        }

        $profileKey = $this->getCurrentProfileKey($user);
        if ($profileKey === null) {
            return array_values(array_unique($user->getRoles()));
        }

        $baseRoles = ['ROLE_USER'];
        $profileRoles = self::PROFILES[$profileKey]['roles'];

        return array_values(array_unique(array_merge($baseRoles, $profileRoles)));
    }

    public function hasEffectiveRole(?User $user, string $role): bool
    {
        if (!$user instanceof User || trim($role) === '') {
            return false;
        }

        $effectiveRoles = $this->getEffectiveRoles($user);
        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($effectiveRoles);

        return \in_array(strtoupper(trim($role)), $reachableRoles, true);
    }

    public function getEffectiveTypeLabel(?User $user): string
    {
        if (!$user instanceof User) {
            return 'Utilisateur';
        }

        if (!$this->isEligible($user)) {
            return $user->getTypeLabel();
        }

        $profileKey = $this->getCurrentProfileKey($user);
        if ($profileKey === null || !isset(self::PROFILES[$profileKey])) {
            return $user->getTypeLabel();
        }

        return self::PROFILES[$profileKey]['typeLabel'];
    }

    public function getCurrentProfileLabel(?User $user): ?string
    {
        $profileKey = $this->getCurrentProfileKey($user);
        if ($profileKey === null || !isset(self::PROFILES[$profileKey])) {
            return null;
        }

        return self::PROFILES[$profileKey]['label'];
    }

    public function getEffectiveUserType(?User $user): ?string
    {
        if (!$user instanceof User) {
            return null;
        }

        if (!$this->isEligible($user)) {
            return $user->getUserType();
        }

        $profileKey = $this->getCurrentProfileKey($user);
        if ($profileKey === null || !isset(self::PROFILES[$profileKey])) {
            return $user->getUserType();
        }

        return self::PROFILES[$profileKey]['userType'];
    }

    public function isPublicSimulation(?User $user): bool
    {
        return $this->getCurrentProfileKey($user) === self::PROFILE_PUBLIC;
    }
}
