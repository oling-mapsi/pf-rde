<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Access\Service\GodModeService;
use App\Domain\Access\Entity\User;
use App\Domain\Agent\Entity\AgentRequest;
use App\Domain\Agent\Entity\AgentRequestAttachment;
use App\Infrastructure\Logging\AuditLogger;
use App\UI\Form\AgentRequestSubmissionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/agents', name: 'agent_')]
#[IsGranted('ROLE_AGENT')]
final class AgentController extends AbstractController
{
    public function __construct(private readonly GodModeService $godModeService)
    {
    }

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): RedirectResponse
    {
        $this->guardEffectiveAgentAccess();

        return $this->redirectToRoute('extranet_dashboard');
    }

    #[Route('/demandes/nouvelle', name: 'request_new', methods: ['GET', 'POST'])]
    public function newRequest(
        Request $request,
        EntityManagerInterface $entityManager,
        AuditLogger $auditLogger,
    ): Response {
        $projectDir = (string) $this->getParameter('kernel.project_dir');

        /** @var User $user */
        $user = $this->getUser();
        $this->guardEffectiveAgentAccess($user);

        $agentRequest = (new AgentRequest())
            ->setRequester($user)
            ->setRequestNumber(sprintf('RDG-%s-%s', date('Ymd'), strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))));

        $form = $this->createForm(AgentRequestSubmissionType::class, $agentRequest, [
            'action' => $this->generateUrl('agent_request_new'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('attachment')->getData();
            if ($file instanceof UploadedFile) {
                $uploadDirectory = sprintf('%s/var/uploads/agent_requests/%s', $projectDir, date('Y/m'));
                (new Filesystem())->mkdir($uploadDirectory);

                $extension = $file->guessExtension() ?: 'bin';
                $filename = sprintf('%s.%s', Uuid::v7()->toRfc4122(), $extension);
                $file->move($uploadDirectory, $filename);

                $attachment = (new AgentRequestAttachment())
                    ->setOriginalName($file->getClientOriginalName())
                    ->setStoragePath(sprintf('agent_requests/%s/%s', date('Y/m'), $filename))
                    ->setMimeType($file->getMimeType() ?? 'application/octet-stream')
                    ->setSizeBytes($file->getSize() ?? 0);

                $agentRequest->addAttachment($attachment);
            }

            $entityManager->persist($agentRequest);

            $auditLogger->log(
                action: 'agent_request.created',
                resourceType: 'agent_request',
                resourceIdentifier: $agentRequest->getRequestNumber(),
                actor: $user,
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
                context: ['status' => $agentRequest->getStatus()]
            );

            $entityManager->flush();
            $this->addFlash('success', 'Demande enregistree.');

            return $this->redirectToRoute('agent_dashboard');
        }

        return $this->render('agent/request_new.html.twig', [
            'form' => $form,
        ]);
    }

    private function guardEffectiveAgentAccess(?User $user = null): void
    {
        $actor = $user ?? ($this->getUser() instanceof User ? $this->getUser() : null);
        if (!$this->godModeService->hasEffectiveRole($actor, 'ROLE_AGENT')) {
            throw $this->createAccessDeniedException('Accès agent indisponible pour le profil simulé courant.');
        }
    }
}
