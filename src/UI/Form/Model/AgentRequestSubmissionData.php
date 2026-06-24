<?php

declare(strict_types=1);

namespace App\UI\Form\Model;

final class AgentRequestSubmissionData
{
    public string $structureType = 'Centre Routier';
    public string $directionService = 'DTCM';
    public ?string $center = null;
    public string $lastName = '';
    public string $firstName = '';
    public string $email = '';
    public string $emailConfirmation = '';
    public string $phoneNumber = '';
    public ?string $orderReference = null;
    public string $urgencyLevel = 'normal';
    public ?string $urgencyJustification = null;
    public string $subject = '';

    /** @var list<string> */
    public array $requestKinds = [];

    /** @var list<string> */
    public array $networkTypes = [];

    public ?string $routeDetails = null;
    public string $geographicArea = '';
    public string $description = '';
    public ?string $additionalInformation = null;
    public bool $hasProvidedData = false;
    public string $deliveryDestination = 'internal';

    /** @var list<string> */
    public array $dataFormats = [];

    public ?string $projectionSystem = 'RGAF09';

    /** @var list<string> */
    public array $mapFormats = [];

    public ?string $mapScale = null;
    public ?string $attachmentDescription = null;
}
