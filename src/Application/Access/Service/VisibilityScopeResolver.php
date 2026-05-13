<?php

declare(strict_types=1);

namespace App\Application\Access\Service;

use App\Domain\Access\VisibilityScope;
use App\Domain\Access\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

final class VisibilityScopeResolver
{
    public function __construct(private readonly GodModeService $godModeService)
    {
    }

    /**
     * @return list<string>
     */
    public function resolveForUser(?UserInterface $user): array
    {
        if ($user === null) {
            return [VisibilityScope::PUBLIC];
        }

        $roles = $user->getRoles();
        if ($user instanceof User) {
            $roles = $this->godModeService->getEffectiveRoles($user);
        }

        if (\in_array('ROLE_ADMIN', $roles, true) || \in_array('ROLE_MANAGER', $roles, true) || \in_array('ROLE_AGENT', $roles, true)) {
            return VisibilityScope::all();
        }

        if (\in_array('ROLE_EXTERNAL', $roles, true)) {
            return [VisibilityScope::PUBLIC, VisibilityScope::EXTERNAL];
        }

        return [VisibilityScope::PUBLIC];
    }
}
