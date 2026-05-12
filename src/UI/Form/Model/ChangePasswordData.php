<?php

declare(strict_types=1);

namespace App\UI\Form\Model;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordData
{
    #[SecurityAssert\UserPassword(message: 'Le mot de passe actuel est incorrect.')]
    public string $currentPassword = '';

    #[Assert\NotBlank]
    public string $newPassword = '';
}

