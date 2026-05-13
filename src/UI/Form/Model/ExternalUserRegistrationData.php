<?php

declare(strict_types=1);

namespace App\UI\Form\Model;

final class ExternalUserRegistrationData
{
    public string $firstName = '';
    public string $lastName = '';
    public string $organizationName = '';
    public string $companySiret = '';
    public string $email = '';
    public string $postalAddress = '';
    public string $accountRequestReason = '';
    public ?string $websiteUrl = null;
    public string $password = '';
}
