<?php

declare(strict_types=1);

namespace App\Domain\Access\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\ExternalResourceRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExternalResourceRequestRepository::class)]
#[ORM\Table(name: 'external_resource_request')]
#[ORM\Index(name: 'idx_external_request_user', columns: ['requester_id'])]
#[ORM\Index(name: 'idx_external_request_status', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class ExternalResourceRequest
{
    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    public const REQUESTER_TYPE_USAGER = 'usager';
    public const REQUESTER_TYPE_PROFESSIONAL = 'professional';
    public const REQUESTER_TYPE_AGENT = 'agent';

    public const REQUEST_KIND_MAP = 'map';
    public const REQUEST_KIND_DATA = 'data';
    public const REQUEST_KIND_MIXED = 'mixed';

    public const DELIVERY_DESTINATION_INTERNAL = 'internal';
    public const DELIVERY_DESTINATION_EXTERNAL = 'external';

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    public const REQUESTER_TYPE_LABELS = [
        self::REQUESTER_TYPE_USAGER => 'Usager',
        self::REQUESTER_TYPE_PROFESSIONAL => 'Professionnel',
        self::REQUESTER_TYPE_AGENT => 'Agent',
    ];

    public const REQUEST_KIND_LABELS = [
        self::REQUEST_KIND_MAP => 'Carte',
        self::REQUEST_KIND_DATA => 'Données',
        self::REQUEST_KIND_MIXED => 'Carte + données',
    ];

    public const STATUS_LABELS = [
        self::STATUS_SUBMITTED => 'Soumise',
        self::STATUS_ACKNOWLEDGED => 'Accusée',
        self::STATUS_IN_REVIEW => 'En analyse',
        self::STATUS_PROCESSING => 'En cours',
        self::STATUS_ON_HOLD => 'En attente',
        self::STATUS_PROCESSED => 'Traitée',
        self::STATUS_REJECTED => 'Rejetée',
        self::STATUS_ARCHIVED => 'Archivée',
    ];

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'requester_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $requester = null;

    #[ORM\Column(type: Types::STRING, length: 40, unique: true, nullable: true)]
    private ?string $requestNumber = null;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['default' => self::REQUESTER_TYPE_PROFESSIONAL])]
    private string $requesterType = self::REQUESTER_TYPE_PROFESSIONAL;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $subject = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $message = '';

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $organizationName = null;

    #[ORM\Column(type: Types::STRING, length: 14, nullable: true)]
    private ?string $companySiret = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $requestKind = null;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $networkTypes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalInformation = null;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $dataFormats = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $projectionSystem = null;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $mapFormats = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $mapScale = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $deliveryDestination = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $privacyConsent = false;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $noticeVersion = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $acknowledgedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['default' => self::STATUS_SUBMITTED])]
    private string $status = self::STATUS_SUBMITTED;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $submittedAt;

    public function __construct()
    {
        $this->submittedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function initializeRequestNumber(): void
    {
        $this->requestNumber = $this->requestNumber ?? $this->buildRequestNumber();
    }

    public function getRequester(): ?User
    {
        return $this->requester;
    }

    public function setRequester(?User $requester): static
    {
        $this->requester = $requester;

        return $this;
    }

    public function getRequestNumber(): ?string
    {
        if ($this->requestNumber === null || $this->requestNumber === '') {
            $this->requestNumber = $this->buildRequestNumber();
        }

        return $this->requestNumber;
    }

    public function setRequestNumber(?string $requestNumber): static
    {
        $value = $requestNumber !== null ? trim($requestNumber) : null;
        $this->requestNumber = $value !== '' ? $value : null;

        return $this;
    }

    public function getRequesterType(): string
    {
        return $this->requesterType;
    }

    public function setRequesterType(string $requesterType): static
    {
        $this->requesterType = strtolower(trim($requesterType));

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = trim($subject);

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = trim($message);

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $value = $lastName !== null ? trim($lastName) : null;
        $this->lastName = $value !== '' ? $value : null;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $value = $firstName !== null ? trim($firstName) : null;
        $this->firstName = $value !== '' ? $value : null;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $value = $email !== null ? strtolower(trim($email)) : null;
        $this->email = $value !== '' ? $value : null;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $value = $phoneNumber !== null ? trim($phoneNumber) : null;
        $this->phoneNumber = $value !== '' ? $value : null;

        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(?string $organizationName): static
    {
        $value = $organizationName !== null ? trim($organizationName) : null;
        $this->organizationName = $value !== '' ? $value : null;

        return $this;
    }

    public function getCompanySiret(): ?string
    {
        return $this->companySiret;
    }

    public function setCompanySiret(?string $companySiret): static
    {
        $normalized = $companySiret !== null ? preg_replace('/\D+/', '', $companySiret) : null;
        $this->companySiret = $normalized !== null && $normalized !== '' ? $normalized : null;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $value = $postalCode !== null ? trim($postalCode) : null;
        $this->postalCode = $value !== '' ? $value : null;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $value = $city !== null ? trim($city) : null;
        $this->city = $value !== '' ? $value : null;

        return $this;
    }

    public function getRequestKind(): ?string
    {
        return $this->requestKind;
    }

    public function setRequestKind(?string $requestKind): static
    {
        $value = $requestKind !== null ? strtolower(trim($requestKind)) : null;
        $this->requestKind = $value !== '' ? $value : null;

        return $this;
    }

    /** @return list<string>|null */
    public function getNetworkTypes(): ?array
    {
        return $this->networkTypes;
    }

    /** @param list<string>|null $networkTypes */
    public function setNetworkTypes(?array $networkTypes): static
    {
        $this->networkTypes = $this->normalizeStringList($networkTypes);

        return $this;
    }

    public function getAdditionalInformation(): ?string
    {
        return $this->additionalInformation;
    }

    public function setAdditionalInformation(?string $additionalInformation): static
    {
        $value = $additionalInformation !== null ? trim($additionalInformation) : null;
        $this->additionalInformation = $value !== '' ? $value : null;

        return $this;
    }

    /** @return list<string>|null */
    public function getDataFormats(): ?array
    {
        return $this->dataFormats;
    }

    /** @param list<string>|null $dataFormats */
    public function setDataFormats(?array $dataFormats): static
    {
        $this->dataFormats = $this->normalizeStringList($dataFormats);

        return $this;
    }

    public function getProjectionSystem(): ?string
    {
        return $this->projectionSystem;
    }

    public function setProjectionSystem(?string $projectionSystem): static
    {
        $value = $projectionSystem !== null ? trim($projectionSystem) : null;
        $this->projectionSystem = $value !== '' ? $value : null;

        return $this;
    }

    /** @return list<string>|null */
    public function getMapFormats(): ?array
    {
        return $this->mapFormats;
    }

    /** @param list<string>|null $mapFormats */
    public function setMapFormats(?array $mapFormats): static
    {
        $this->mapFormats = $this->normalizeStringList($mapFormats);

        return $this;
    }

    public function getMapScale(): ?string
    {
        return $this->mapScale;
    }

    public function setMapScale(?string $mapScale): static
    {
        $value = $mapScale !== null ? trim($mapScale) : null;
        $this->mapScale = $value !== '' ? $value : null;

        return $this;
    }

    public function getDeliveryDestination(): ?string
    {
        return $this->deliveryDestination;
    }

    public function setDeliveryDestination(?string $deliveryDestination): static
    {
        $value = $deliveryDestination !== null ? strtolower(trim($deliveryDestination)) : null;
        $this->deliveryDestination = $value !== '' ? $value : null;

        return $this;
    }

    public function isPrivacyConsent(): bool
    {
        return $this->privacyConsent;
    }

    public function setPrivacyConsent(bool $privacyConsent): static
    {
        $this->privacyConsent = $privacyConsent;

        return $this;
    }

    public function getNoticeVersion(): ?string
    {
        return $this->noticeVersion;
    }

    public function setNoticeVersion(?string $noticeVersion): static
    {
        $value = $noticeVersion !== null ? trim($noticeVersion) : null;
        $this->noticeVersion = $value !== '' ? $value : null;

        return $this;
    }

    public function getAcknowledgedAt(): ?\DateTimeImmutable
    {
        return $this->acknowledgedAt;
    }

    public function setAcknowledgedAt(?\DateTimeImmutable $acknowledgedAt): static
    {
        $this->acknowledgedAt = $acknowledgedAt;

        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = strtolower(trim($status));

        return $this;
    }

    public function getSubmittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getRequesterDisplayName(): string
    {
        $fullName = trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        if ($this->requester instanceof User) {
            return $this->requester->getDisplayName();
        }

        return $this->email ?? 'Demandeur non renseigné';
    }

    public function getRequesterTypeLabel(): string
    {
        return self::REQUESTER_TYPE_LABELS[$this->requesterType] ?? $this->requesterType;
    }

    public function getRequestKindLabel(): string
    {
        $requestKind = $this->requestKind ?? '';

        return self::REQUEST_KIND_LABELS[$requestKind] ?? ($requestKind !== '' ? $requestKind : 'Non précisé');
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * @param list<string>|null $values
     *
     * @return list<string>|null
     */
    private function normalizeStringList(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $values,
        ), static fn (string $value): bool => $value !== ''));

        return $normalized !== [] ? $normalized : null;
    }

    private function buildRequestNumber(): string
    {
        return sprintf(
            'RDG-EXT-%s-%s',
            $this->submittedAt->format('Ymd'),
            strtoupper(substr(str_replace('-', '', $this->getUuid()->toRfc4122()), 0, 6)),
        );
    }

    public function __toString(): string
    {
        return $this->requestNumber ?? $this->subject;
    }
}
