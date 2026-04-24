<?php

declare(strict_types=1);

namespace App\Domain\Agent\Entity;

use App\Domain\Access\Entity\User;
use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\AgentRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgentRequestRepository::class)]
#[ORM\Table(name: 'agent_request')]
#[ORM\UniqueConstraint(name: 'uniq_agent_request_number', columns: ['request_number'])]
#[ORM\Index(name: 'idx_agent_request_status', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class AgentRequest
{
    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    #[ORM\ManyToOne(targetEntity: AgentRequestType::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?AgentRequestType $requestType = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $requester = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedTo = null;

    #[ORM\Column(type: Types::STRING, length: 40, name: 'request_number')]
    private string $requestNumber = '';

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(type: Types::STRING, length: 32, options: ['default' => 'submitted'])]
    private string $status = 'submitted';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $submittedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $payload = null;

    /** @var Collection<int, AgentRequestAttachment> */
    #[ORM\OneToMany(mappedBy: 'agentRequest', targetEntity: AgentRequestAttachment::class, cascade: ['persist', 'remove'])]
    private Collection $attachments;

    public function __construct()
    {
        $this->submittedAt = new \DateTimeImmutable();
        $this->attachments = new ArrayCollection();
    }

    public function getRequestType(): ?AgentRequestType
    {
        return $this->requestType;
    }

    public function setRequestType(?AgentRequestType $requestType): static
    {
        $this->requestType = $requestType;

        return $this;
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

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function getRequestNumber(): string
    {
        return $this->requestNumber;
    }

    public function setRequestNumber(string $requestNumber): static
    {
        $this->requestNumber = $requestNumber;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /** @param array<string, mixed>|null $payload */
    public function setPayload(?array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    /** @return Collection<int, AgentRequestAttachment> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(AgentRequestAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setAgentRequest($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->requestNumber;
    }
}
