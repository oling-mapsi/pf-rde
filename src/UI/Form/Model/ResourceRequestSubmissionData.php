<?php

declare(strict_types=1);

namespace App\UI\Form\Model;

use App\Domain\Access\Entity\ExternalResourceRequest;

final class ResourceRequestSubmissionData
{
    public string $requesterType = ExternalResourceRequest::REQUESTER_TYPE_USAGER;
    public string $lastName = '';
    public string $firstName = '';
    public string $email = '';
    public string $emailConfirmation = '';
    public ?string $phoneNumber = null;
    public ?string $organizationName = null;
    public ?string $companySiret = null;
    public string $postalCode = '';
    public string $city = '';
    public string $subject = '';

    /** @var list<string> */
    public array $requestKinds = [];

    /** @var list<string> */
    public array $networkTypes = [];

    public string $message = '';
    public ?string $additionalInformation = null;

    /** @var list<string> */
    public array $dataFormats = [];

    public ?string $projectionSystem = null;

    /** @var list<string> */
    public array $mapFormats = [];

    public ?string $mapScale = null;
    public bool $privacyConsent = false;
}
