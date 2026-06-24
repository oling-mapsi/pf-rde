<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Access\Service\GodModeService;
use App\Application\Notification\RequestNotificationService;
use App\Domain\Access\Entity\ExternalResourceRequest;
use App\Domain\Access\Entity\User;
use App\Infrastructure\Logging\AuditLogger;
use App\UI\Form\ExternalResourceRequestSubmissionType;
use App\UI\Form\Model\ResourceRequestSubmissionData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    ): Response {
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

        $data = $this->createPrefilledProfessionalData($user);
        $form = $this->createForm(ExternalResourceRequestSubmissionType::class, $data, [
            'submission_context' => 'professional',
            'show_requester_type' => false,
            'action' => $this->generateUrl('extranet_request_new'),
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

        return $this->render('extranet/request_new.html.twig', [
            'form' => $form,
        ]);
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
