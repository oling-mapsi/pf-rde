<?php

declare(strict_types=1);

namespace App\Application\Access\Service;

use App\Domain\Access\Entity\Role;
use App\Domain\Access\Entity\User;
use App\Infrastructure\Repository\RoleRepository;
use App\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SsoUserProvisioner
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<string> $internalRoleCodes
     */
    public function provision(
        string $subject,
        string $email,
        ?string $firstName,
        ?string $lastName,
        ?string $displayName,
        array $internalRoleCodes,
    ): User {
        $normalizedSubject = trim($subject);
        $normalizedEmail = strtolower(trim($email));

        $user = $this->userRepository->findOneBy(['ssoSubject' => $normalizedSubject]);
        if (!$user instanceof User) {
            $user = $this->userRepository->findOneBy(['email' => $normalizedEmail]);
        }

        if (!$user instanceof User) {
            $user = new User();
            $user->setPassword('');
            $this->entityManager->persist($user);
        }

        $computedDisplayName = $displayName !== null ? trim($displayName) : '';
        if ($computedDisplayName === '') {
            $computedDisplayName = trim(sprintf('%s %s', (string) $firstName, (string) $lastName));
        }
        if ($computedDisplayName === '') {
            $computedDisplayName = $normalizedEmail;
        }

        $normalizedRoleCodes = array_values(array_unique(array_map(
            static fn (string $roleCode): string => strtoupper(trim($roleCode)),
            array_filter(
                $internalRoleCodes,
                static fn (mixed $value): bool => is_string($value) && trim($value) !== ''
            )
        )));

        foreach ($normalizedRoleCodes as $roleCode) {
            $user->addRole($this->resolveRole($roleCode));
        }

        $managedRoleCodes = ['ROLE_AGENT', 'ROLE_ADMIN'];
        foreach ($user->getRoleEntities()->toArray() as $existingRole) {
            if (!$existingRole instanceof Role) {
                continue;
            }

            $code = strtoupper($existingRole->getCode());
            if (\in_array($code, $managedRoleCodes, true) && !\in_array($code, $normalizedRoleCodes, true)) {
                $user->removeRole($existingRole);
            }
        }

        $user
            ->setEmail($normalizedEmail)
            ->setSsoSubject($normalizedSubject)
            ->setAuthProvider(User::AUTH_PROVIDER_SSO)
            ->setIsActive(true)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setDisplayName($computedDisplayName)
            ->setUserType(\in_array('ROLE_ADMIN', $normalizedRoleCodes, true) ? User::TYPE_ADMIN_SSO : User::TYPE_AGENT_SSO);

        $this->entityManager->flush();

        return $user;
    }

    private function resolveRole(string $code): Role
    {
        $normalizedCode = strtoupper(trim($code));
        $role = $this->roleRepository->findOneBy(['code' => $normalizedCode]);
        if ($role instanceof Role) {
            return $role;
        }

        $label = match ($normalizedCode) {
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_AGENT' => 'Agent',
            default => $normalizedCode,
        };

        $description = match ($normalizedCode) {
            'ROLE_ADMIN' => 'Accès complet au back-office',
            'ROLE_AGENT' => 'Accès aux interfaces internes agents',
            default => 'Rôle synchronisé depuis le SSO Microsoft Entra ID',
        };

        $role = (new Role($label, $normalizedCode))->setDescription($description);
        $this->entityManager->persist($role);

        return $role;
    }
}
