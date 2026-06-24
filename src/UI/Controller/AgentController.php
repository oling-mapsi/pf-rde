<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Access\Service\GodModeService;
use App\Application\Notification\RequestNotificationService;
use App\Domain\Access\Entity\User;
use App\Domain\Agent\Entity\AgentRequest;
use App\Domain\Agent\Entity\AgentRequestAttachment;
use App\Domain\Agent\Entity\AgentRequestType;
use App\Infrastructure\Logging\AuditLogger;
use App\Infrastructure\Repository\AgentRequestTypeRepository;
use App\UI\Form\AgentRequestRichSubmissionType;
use App\UI\Form\Model\AgentRequestSubmissionData;
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
        AgentRequestTypeRepository $agentRequestTypeRepository,
        AuditLogger $auditLogger,
        RequestNotificationService $requestNotificationService,
    ): Response {
        $projectDir = (string) $this->getParameter('kernel.project_dir');

        /** @var User $user */
        $user = $this->getUser();
        $this->guardEffectiveAgentAccess($user);

        $data = $this->createPrefilledSubmissionData($user);
        $agentRequest = (new AgentRequest())
            ->setRequester($user)
            ->setRequestNumber(sprintf('RDG-%s-%s', date('Ymd'), strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))));

        $form = $this->createForm(AgentRequestRichSubmissionType::class, $data, [
            'action' => $this->generateUrl('agent_request_new'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestType = $this->resolveRequestType($data->requestKinds, $agentRequestTypeRepository);
            $agentRequest
                ->setRequestType($requestType)
                ->setTitle($data->subject)
                ->setDescription($data->description)
                ->setPayload([
                    'structureType' => $data->structureType,
                    'directionService' => $data->directionService,
                    'center' => $data->center,
                    'lastName' => $data->lastName,
                    'firstName' => $data->firstName,
                    'email' => $data->email,
                    'phoneNumber' => $data->phoneNumber,
                    'orderReference' => $data->orderReference,
                    'urgencyLevel' => $data->urgencyLevel,
                    'urgencyJustification' => $data->urgencyJustification,
                    'requestKinds' => $data->requestKinds,
                    'networkTypes' => $data->networkTypes,
                    'routeDetails' => $data->routeDetails,
                    'geographicArea' => $data->geographicArea,
                    'additionalInformation' => $data->additionalInformation,
                    'hasProvidedData' => $data->hasProvidedData,
                    'deliveryDestination' => $data->deliveryDestination,
                    'dataFormats' => $data->dataFormats,
                    'projectionSystem' => $data->projectionSystem,
                    'mapFormats' => $data->mapFormats,
                    'mapScale' => $data->mapScale,
                    'attachmentDescription' => $data->attachmentDescription,
                ]);

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
                context: [
                    'status' => $agentRequest->getStatus(),
                    'urgency' => $data->urgencyLevel,
                    'request_kinds' => $data->requestKinds,
                ]
            );

            $entityManager->flush();
            $requestNotificationService->sendAgentRequestSubmitted($agentRequest);
            $this->addFlash('success', 'Demande enregistree.');

            return $this->redirectToRoute('agent_dashboard');
        }

        return $this->render('agent/request_new.html.twig', [
            'form' => $form,
        ]);
    }

    private function createPrefilledSubmissionData(User $user): AgentRequestSubmissionData
    {
        $data = new AgentRequestSubmissionData();
        $data->lastName = $user->getLastName() ?? '';
        $data->firstName = $user->getFirstName() ?? '';
        $data->email = $user->getEmail();
        $data->emailConfirmation = $user->getEmail();

        return $data;
    }

    /**
     * @param list<string> $requestKinds
     */
    private function resolveRequestType(array $requestKinds, AgentRequestTypeRepository $repository): AgentRequestType
    {
        $code = match (true) {
            \in_array('map', $requestKinds, true) && \in_array('data', $requestKinds, true) => 'MIXED_REQUEST',
            \in_array('map', $requestKinds, true) => 'MAP_REQUEST',
            default => 'DATA_REQUEST',
        };

        $requestType = $repository->findOneBy(['code' => $code, 'active' => true]);
        if ($requestType instanceof AgentRequestType) {
            return $requestType;
        }

        $fallback = $repository->findOneBy(['code' => 'MAP_REQUEST', 'active' => true])
            ?? $repository->findOneBy(['code' => 'DATA_REQUEST', 'active' => true]);

        if (!$fallback instanceof AgentRequestType) {
            throw new \RuntimeException('Aucun type de demande agent actif n’est configuré.');
        }

        return $fallback;
    }

    private function guardEffectiveAgentAccess(?User $user = null): void
    {
        $actor = $user ?? ($this->getUser() instanceof User ? $this->getUser() : null);
        if (!$this->godModeService->hasEffectiveRole($actor, 'ROLE_AGENT')) {
            throw $this->createAccessDeniedException('Accès agent indisponible pour le profil simulé courant.');
        }
    }
}
