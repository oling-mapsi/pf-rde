<?php

declare(strict_types=1);

namespace App\Application\Access\Service;

use App\Domain\Access\VisibilityScope;
use Symfony\Component\Security\Core\User\UserInterface;

final class VisibilityScopeResolver
{
    /**
     * @return list<string>
     */
    public function resolveForUser(?UserInterface $user): array
    {
        if ($user === null) {
            return [VisibilityScope::PUBLIC];
        }

        $roles = $user->getRoles();

        if (\in_array('ROLE_ADMIN', $roles, true) || \in_array('ROLE_AGENT', $roles, true)) {
            return VisibilityScope::all();
        }

        if (\in_array('ROLE_EXTERNAL', $roles, true)) {
            return [VisibilityScope::PUBLIC, VisibilityScope::EXTERNAL];
        }

        return [VisibilityScope::PUBLIC];
    }
}

