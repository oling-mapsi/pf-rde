<?php

declare(strict_types=1);

namespace App\UI\Form\Model;

use App\Domain\Access\Entity\User;

final class ExternalUserRegistrationData
{
    public string $userType = User::TYPE_EXTERNAL;
    public string $firstName = '';
    public string $lastName = '';
    public ?string $organizationName = null;
    public ?string $websiteUrl = null;
    public string $email = '';
    public string $password = '';
}
