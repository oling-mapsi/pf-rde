<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Domain\Access\Entity\AuditLog;
use App\Domain\Access\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Attribute\WithMonologChannel;

#[WithMonologChannel('audit')]
final class AuditLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed>|null $context
     */
    public function log(
        string $action,
        string $resourceType,
        ?string $resourceIdentifier = null,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $context = null,
        bool $sensitive = false,
    ): void {
        $entry = (new AuditLog())
            ->setAction($action)
            ->setResourceType($resourceType)
            ->setResourceIdentifier($resourceIdentifier)
            ->setActor($actor)
            ->setIpAddress($ipAddress)
            ->setUserAgent($userAgent)
            ->setContext($context)
            ->setSensitive($sensitive);

        $this->entityManager->persist($entry);

        $this->logger->info('audit_event', [
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_identifier' => $resourceIdentifier,
            'actor' => $actor?->getUserIdentifier(),
            'ip' => $ipAddress,
            'sensitive' => $sensitive,
            'context' => $context,
        ]);
    }
}
