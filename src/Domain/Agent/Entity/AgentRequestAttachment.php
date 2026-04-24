<?php

declare(strict_types=1);

namespace App\Domain\Agent\Entity;

use App\Domain\Common\Entity\Traits\IdentifierTrait;
use App\Domain\Common\Entity\Traits\MetadataTrait;
use App\Domain\Common\Entity\Traits\TimestampableTrait;
use App\Infrastructure\Repository\AgentRequestAttachmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgentRequestAttachmentRepository::class)]
#[ORM\Table(name: 'agent_request_attachment')]
#[ORM\HasLifecycleCallbacks]
class AgentRequestAttachment
{
    use IdentifierTrait;
    use TimestampableTrait;
    use MetadataTrait;

    #[ORM\ManyToOne(targetEntity: AgentRequest::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AgentRequest $agentRequest = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $originalName = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $storagePath = '';

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $mimeType = '';

    #[ORM\Column(type: Types::BIGINT)]
    private int $sizeBytes = 0;

    public function getAgentRequest(): ?AgentRequest
    {
        return $this->agentRequest;
    }

    public function setAgentRequest(?AgentRequest $agentRequest): static
    {
        $this->agentRequest = $agentRequest;

        return $this;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function setStoragePath(string $storagePath): static
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function setSizeBytes(int $sizeBytes): static
    {
        $this->sizeBytes = $sizeBytes;

        return $this;
    }

    public function __toString(): string
    {
        return $this->originalName;
    }
}
