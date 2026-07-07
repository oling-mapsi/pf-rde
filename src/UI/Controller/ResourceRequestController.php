<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Access\Service\GodModeService;
use App\Application\Notification\RequestNotificationService;
use App\Domain\Access\Entity\ExternalResourceRequest;
use App\Domain\Access\Entity\User;
use App\Domain\Agent\Entity\AgentRequest;
use App\Domain\Agent\Entity\AgentRequestAttachment;
use App\Domain\Agent\Entity\AgentRequestType;
use App\Infrastructure\Logging\AuditLogger;
use App\Infrastructure\Repository\AgentRequestTypeRepository;
use App\UI\Form\AgentRequestRichSubmissionType;
use App\UI\Form\Model\AgentRequestSubmissionData;
use App\UI\Form\ExternalResourceRequestSubmissionType;
use App\UI\Form\Model\ResourceRequestSubmissionData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

final class ResourceRequestController extends AbstractController
{
    private const NOTICE_VERSION = 'requests-v1';

    public function __construct(private readonly GodModeService $godModeService)
    {
    }

    #[Route('/demande/cartes-donnees', name: 'app_resource_request_public', methods: ['GET', 'POST'])]
    public function publicRequest(
        Request $request,
        EntityManagerInterface $entityManager,
        AuditLogger $auditLogger,
        RequestNotificationService $requestNotificationService,
        AgentRequestTypeRepository $agentRequestTypeRepository,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser() instanceof User ? $this->getUser() : null;
        if ($user instanceof User && !$this->godModeService->isPublicSimulation($user)) {
            if ($this->godModeService->hasEffectiveRole($user, 'ROLE_AGENT')) {
                return $this->handleAgentRequest(
                    $request,
                    $entityManager,
                    $agentRequestTypeRepository,
                    $auditLogger,
                    $requestNotificationService,
                    $user,
                );
            }

            return $this->handleProfessionalRequest(
                $request,
                $entityManager,
                $auditLogger,
                $requestNotificationService,
                $user,
                true,
            );
        }

        $data = new ResourceRequestSubmissionData();
        $form = $this->createForm(ExternalResourceRequestSubmissionType::class, $data, [
            'submission_context' => 'public',
            'show_requester_type' => true,
            'action' => $this->generateUrl('app_resource_request_public'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resourceRequest = $this->buildResourceRequestFromData($data, null);
            $entityManager->persist($resourceRequest);

            $auditLogger->log(
                action: 'external_resource_request.public_created',
                resourceType: 'external_resource_request',
                resourceIdentifier: $resourceRequest->getRequestNumber(),
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
                context: [
                    'requester_type' => $resourceRequest->getRequesterType(),
                    'request_kind' => $resourceRequest->getRequestKind(),
                ]
            );

            $entityManager->flush();
            $requestNotificationService->sendExternalRequestSubmitted($resourceRequest);

            $this->addFlash('success', sprintf(
                'Votre demande a été enregistrée sous le numéro %s.',
                $resourceRequest->getRequestNumber() ?? 'en cours'
            ));

            return $this->redirectToRoute('app_resource_request_public');
        }

        return $this->render('public/resource_request.html.twig', [
            'form' => $form,
            'isPublicRequest' => true,
            'requestMode' => 'public',
        ]);
    }

    #[Route('/extranet/demandes/nouvelle', name: 'extranet_request_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function professionalRequest(
        Request $request,
        EntityManagerInterface $entityManager,
        AuditLogger $auditLogger,
        RequestNotificationService $requestNotificationService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->godModeService->hasEffectiveRole($user, 'ROLE_AGENT')) {
            return $this->redirectToRoute('agent_request_new');
        }

        return $this->handleProfessionalRequest(
            $request,
            $entityManager,
            $auditLogger,
            $requestNotificationService,
            $user,
            false,
        );
    }

    private function createPrefilledProfessionalData(User $user): ResourceRequestSubmissionData
    {
        $data = new ResourceRequestSubmissionData();
        $data->requesterType = ExternalResourceRequest::REQUESTER_TYPE_PROFESSIONAL;
        $data->lastName = $user->getLastName() ?? '';
        $data->firstName = $user->getFirstName() ?? '';
        $data->email = $user->getEmail();
        $data->phoneNumber = null;
        $data->organizationName = $user->getOrganizationName();
        $data->companySiret = $user->getCompanySiret();
        $data->privacyConsent = true;

        return $data;
    }

    private function handleProfessionalRequest(
        Request $request,
        EntityManagerInterface $entityManager,
        AuditLogger $auditLogger,
        RequestNotificationService $requestNotificationService,
        User $user,
        bool $renderOnPublicPage,
    ): Response {
        $data = $this->createPrefilledProfessionalData($user);
        $form = $this->createForm(ExternalResourceRequestSubmissionType::class, $data, [
            'submission_context' => 'professional',
            'show_requester_type' => false,
            'action' => $this->generateUrl($renderOnPublicPage ? 'app_resource_request_public' : 'extranet_request_new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resourceRequest = $this->buildResourceRequestFromData($data, $user);
            $entityManager->persist($resourceRequest);

            $auditLogger->log(
                action: 'external_resource_request.professional_created',
                resourceType: 'external_resource_request',
                resourceIdentifier: $resourceRequest->getRequestNumber(),
                actor: $user,
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
                context: [
                    'request_kind' => $resourceRequest->getRequestKind(),
                ]
            );

            $entityManager->flush();
            $requestNotificationService->sendExternalRequestSubmitted($resourceRequest);
            $this->addFlash('success', sprintf(
                'Votre demande a été transmise sous le numéro %s.',
                $resourceRequest->getRequestNumber() ?? 'en cours'
            ));

            return $this->redirectToRoute('extranet_dashboard');
        }

        if ($renderOnPublicPage) {
            return $this->render('public/resource_request.html.twig', [
                'form' => $form,
                'isPublicRequest' => false,
                'requestMode' => 'professional',
            ]);
        }

        return $this->render('extranet/request_new.html.twig', [
            'form' => $form,
        ]);
    }

    private function handleAgentRequest(
        Request $request,
        EntityManagerInterface $entityManager,
        AgentRequestTypeRepository $agentRequestTypeRepository,
        AuditLogger $auditLogger,
        RequestNotificationService $requestNotificationService,
        User $user,
    ): Response {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $data = $this->createPrefilledAgentData($user);
        $agentRequest = (new AgentRequest())
            ->setRequester($user)
            ->setRequestNumber(sprintf('RDG-%s-%s', date('Ymd'), strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))));

        $form = $this->createForm(AgentRequestRichSubmissionType::class, $data, [
            'action' => $this->generateUrl('app_resource_request_public'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestType = $this->resolveAgentRequestType($data->requestKinds, $agentRequestTypeRepository);
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

        return $this->render('public/resource_request.html.twig', [
            'form' => $form,
            'isPublicRequest' => false,
            'requestMode' => 'agent',
        ]);
    }

    private function createPrefilledAgentData(User $user): AgentRequestSubmissionData
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
    private function resolveAgentRequestType(array $requestKinds, AgentRequestTypeRepository $repository): AgentRequestType
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

    private function buildResourceRequestFromData(ResourceRequestSubmissionData $data, ?User $requester): ExternalResourceRequest
    {
        $resourceRequest = (new ExternalResourceRequest())
            ->setRequester($requester)
            ->setRequesterType($data->requesterType)
            ->setLastName($data->lastName)
            ->setFirstName($data->firstName)
            ->setEmail($data->email)
            ->setPhoneNumber($data->phoneNumber)
            ->setOrganizationName($data->organizationName)
            ->setCompanySiret($data->companySiret)
            ->setPostalCode($data->postalCode)
            ->setCity($data->city)
            ->setSubject($data->subject)
            ->setMessage($data->message)
            ->setAdditionalInformation($data->additionalInformation)
            ->setRequestKind($this->resolveRequestKind($data->requestKinds))
            ->setNetworkTypes($data->networkTypes)
            ->setDataFormats($data->dataFormats)
            ->setProjectionSystem($data->projectionSystem)
            ->setMapFormats($data->mapFormats)
            ->setMapScale($data->mapScale)
            ->setPrivacyConsent($data->privacyConsent)
            ->setNoticeVersion(self::NOTICE_VERSION)
            ->setStatus(ExternalResourceRequest::STATUS_SUBMITTED);

        return $resourceRequest;
    }

    /**
     * @param list<string> $requestKinds
     */
    private function resolveRequestKind(array $requestKinds): ?string
    {
        $hasMap = \in_array(ExternalResourceRequest::REQUEST_KIND_MAP, $requestKinds, true);
        $hasData = \in_array(ExternalResourceRequest::REQUEST_KIND_DATA, $requestKinds, true);

        if ($hasMap && $hasData) {
            return ExternalResourceRequest::REQUEST_KIND_MIXED;
        }

        if ($hasMap) {
            return ExternalResourceRequest::REQUEST_KIND_MAP;
        }

        if ($hasData) {
            return ExternalResourceRequest::REQUEST_KIND_DATA;
        }

        return null;
    }
}
