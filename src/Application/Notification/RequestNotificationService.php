<?php

declare(strict_types=1);

namespace App\Application\Notification;

use App\Domain\Access\Entity\ExternalResourceRequest;
use App\Domain\Agent\Entity\AgentRequest;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class RequestNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(string:APP_REGISTRATION_FROM_EMAIL)%')]
        private readonly string $fromEmail,
    ) {
    }

    public function sendExternalRequestSubmitted(ExternalResourceRequest $request): void
    {
        $email = $request->getEmail();
        if ($email === null || $email === '') {
            return;
        }

        $this->sendMessage(
            messageType: 'external_request_submitted',
            toEmail: $email,
            toName: $request->getRequesterDisplayName(),
            subject: sprintf('Confirmation de votre demande %s', $request->getRequestNumber() ?? ''),
            htmlTemplate: 'emails/external_request_submitted.html.twig',
            textTemplate: 'emails/external_request_submitted.txt.twig',
            context: [
                'request' => $request,
            ],
        );
    }

    public function sendExternalRequestStatusUpdated(ExternalResourceRequest $request): void
    {
        $email = $request->getEmail();
        if ($email === null || $email === '') {
            return;
        }

        $this->sendMessage(
            messageType: 'external_request_status_updated',
            toEmail: $email,
            toName: $request->getRequesterDisplayName(),
            subject: sprintf('Mise à jour de votre demande %s', $request->getRequestNumber() ?? ''),
            htmlTemplate: 'emails/external_request_status_updated.html.twig',
            textTemplate: 'emails/external_request_status_updated.txt.twig',
            context: [
                'request' => $request,
            ],
        );
    }

    public function sendAgentRequestSubmitted(AgentRequest $request): void
    {
        $email = $request->getRequester()?->getEmail();
        if ($email === null || $email === '') {
            return;
        }

        $this->sendMessage(
            messageType: 'agent_request_submitted',
            toEmail: $email,
            toName: $request->getRequester()?->getDisplayName() ?? $email,
            subject: sprintf('Confirmation de votre demande interne %s', $request->getRequestNumber()),
            htmlTemplate: 'emails/agent_request_submitted.html.twig',
            textTemplate: 'emails/agent_request_submitted.txt.twig',
            context: [
                'request' => $request,
            ],
        );
    }

    public function sendAgentRequestStatusUpdated(AgentRequest $request): void
    {
        $email = $request->getRequester()?->getEmail();
        if ($email === null || $email === '') {
            return;
        }

        $this->sendMessage(
            messageType: 'agent_request_status_updated',
            toEmail: $email,
            toName: $request->getRequester()?->getDisplayName() ?? $email,
            subject: sprintf('Suivi de votre demande interne %s', $request->getRequestNumber()),
            htmlTemplate: 'emails/agent_request_status_updated.html.twig',
            textTemplate: 'emails/agent_request_status_updated.txt.twig',
            context: [
                'request' => $request,
            ],
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function sendMessage(
        string $messageType,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlTemplate,
        string $textTemplate,
        array $context,
    ): void {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, 'Routes de Guadeloupe'))
                ->to(new Address($toEmail, $toName))
                ->subject($subject)
                ->htmlTemplate($htmlTemplate)
                ->textTemplate($textTemplate)
                ->context($context);

            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            $this->logger->error('Request notification email failed', [
                'type' => $messageType,
                'email' => $toEmail,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
