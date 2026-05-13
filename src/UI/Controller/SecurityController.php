<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Access\Service\SsoUserProvisioner;
use App\Application\Access\Service\GodModeService;
use App\Application\Access\Sso\SsoAuthenticationException;
use App\Application\Access\Sso\Office365OidcClient;
use App\Application\Access\Sso\SsoRoleMapper;
use App\Domain\Access\Entity\User;
use App\Infrastructure\Logging\AuditLogger;
use App\Security\LoginFormAuthenticator;
use App\UI\Form\ChangePasswordType;
use App\UI\Form\Model\ChangePasswordData;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Attribute\WithMonologChannel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[WithMonologChannel('audit')]
final class SecurityController extends AbstractController
{
    public function __construct(
        private readonly Office365OidcClient $office365OidcClient,
        private readonly SsoRoleMapper $ssoRoleMapper,
        private readonly SsoUserProvisioner $ssoUserProvisioner,
        private readonly AuditLogger $auditLogger,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.sso.o365.deny_if_unmapped_roles%')]
        private readonly bool $denyIfUnmappedRoles,
        #[Autowire('%app.sso.o365.verbose_user_errors%')]
        private readonly bool $verboseUserErrors,
        #[Autowire('%app.security.password_min_length%')]
        private readonly int $passwordMinLength,
        private readonly GodModeService $godModeService,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route('/connexion', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_private_home');
        }
        if ($user instanceof User) {
            $this->resetInconsistentAuthenticationState();
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/deconnexion', name: 'app_logout', methods: ['POST'])]
    public function logout(): void
    {
        throw new \LogicException('Cette methode est interceptee par le firewall.');
    }

    #[Route('/espace-prive', name: 'app_private_home', methods: ['GET'])]
    public function privateHome(AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $this->godModeService->isPublicSimulation($user)) {
            return $this->redirectToRoute('app_home');
        }

        if ($authorizationChecker->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('extranet_dashboard');
        }
        if ($user instanceof User) {
            $this->resetInconsistentAuthenticationState();
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/espace-prive/mode-dieu/profil', name: 'app_god_mode_profile_switch', methods: ['POST'])]
    public function switchGodModeProfile(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->godModeService->isEligible($user)) {
            throw $this->createAccessDeniedException('Mode Dieu non autorisé.');
        }

        if (!$this->isCsrfTokenValid('god_mode_profile_switch', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_private_home');
        }

        $requestedProfile = trim((string) $request->request->get('profile'));
        if ($requestedProfile === '') {
            $this->godModeService->clearCurrentProfile($user);
            $this->addFlash('info', 'Simulation de profil désactivée.');

            return $this->redirectToRoute('app_private_home');
        }

        if (!$this->godModeService->setCurrentProfile($user, $requestedProfile)) {
            $this->addFlash('error', 'Profil de simulation invalide.');

            return $this->redirectToRoute('app_private_home');
        }

        $profileLabel = $this->godModeService->getCurrentProfileLabel($user);
        $this->addFlash('success', sprintf('Profil simulé actif: %s', $profileLabel ?? $requestedProfile));

        if ($this->godModeService->isPublicSimulation($user)) {
            return $this->redirectToRoute('app_home');
        }

        return $this->redirectToRoute('app_private_home');
    }

    #[Route('/espace-prive/mot-de-passe', name: 'app_account_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->getAuthProvider() === User::AUTH_PROVIDER_SSO) {
            $this->addFlash('info', 'Ce compte est géré via Microsoft 365. Le mot de passe se modifie côté SSO.');

            return $this->redirectToRoute('app_private_home');
        }

        $data = new ChangePasswordData();
        $form = $this->createForm(ChangePasswordType::class, $data, [
            'password_min_length' => $this->passwordMinLength,
            'action' => $this->generateUrl('app_account_change_password'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $data->newPassword));
            $entityManager->flush();

            $this->auditLogger->log(
                action: 'user.password_changed',
                resourceType: 'user',
                resourceIdentifier: (string) $user->getId(),
                actor: $user,
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
                sensitive: true,
                flush: true,
            );

            $this->addFlash('success', 'Mot de passe mis à jour.');

            return $this->redirectToRoute('app_private_home');
        }

        return $this->render('security/change_password.html.twig', [
            'form' => $form,
            'passwordMinLength' => max(8, $this->passwordMinLength),
        ]);
    }

    #[Route('/sso/connexion', name: 'app_sso_login', methods: ['GET', 'POST'])]
    public function ssoLogin(): Response
    {
        $this->auditLogger->log(
            action: 'sso.login.initiated',
            resourceType: 'sso',
            resourceIdentifier: 'office365',
            actor: $this->getUser() instanceof User ? $this->getUser() : null,
            context: [
                'route' => 'app_sso_login',
                'already_authenticated' => $this->getUser() !== null,
            ],
            sensitive: true,
            flush: true,
        );

        if (!$this->office365OidcClient->isEnabled()) {
            $this->addFlash('error', 'Le SSO Microsoft 365 n’est pas encore configuré.');

            return $this->redirectToRoute('app_login');
        }

        return new RedirectResponse($this->office365OidcClient->buildAuthorizationUrl());
    }

    #[Route('/sso/callback', name: 'app_sso_callback', methods: ['GET'])]
    public function ssoCallback(Request $request, Security $security): Response
    {
        $this->auditLogger->log(
            action: 'sso.callback.received',
            resourceType: 'sso',
            resourceIdentifier: 'office365',
            ipAddress: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
            context: [
                'query_has_code' => $request->query->has('code'),
                'query_has_error' => $request->query->has('error'),
                'query_error' => (string) $request->query->get('error', ''),
                'query_has_state' => $request->query->has('state'),
            ],
            sensitive: true,
            flush: true,
        );

        try {
            $identity = $this->office365OidcClient->authenticateFromCallback($request);
            $roleMapping = $this->ssoRoleMapper->mapDetailed($identity->externalRoles);

            if ($this->denyIfUnmappedRoles && $roleMapping->unmappedExternalRoles !== []) {
                throw new SsoAuthenticationException(
                    reasonCode: 'unmapped_external_roles',
                    userMessage: sprintf(
                        'Rôles SSO non reconnus côté portail: %s. Contactez l’équipe IT pour aligner le mapping.',
                        implode(', ', $roleMapping->unmappedExternalRoles)
                    ),
                    context: [
                        'external_roles' => $roleMapping->externalRoles,
                        'unmapped_external_roles' => $roleMapping->unmappedExternalRoles,
                    ],
                );
            }

            if ($roleMapping->internalRoles === []) {
                throw new SsoAuthenticationException(
                    reasonCode: 'no_internal_roles_mapped',
                    userMessage: 'Aucun rôle SIG interne n’a pu être attribué à partir du token SSO.',
                    context: [
                        'external_roles' => $roleMapping->externalRoles,
                        'matched_external_roles' => $roleMapping->matchedExternalRoles,
                    ],
                );
            }

            $user = $this->ssoUserProvisioner->provision(
                subject: $identity->subject,
                email: $identity->email,
                firstName: $identity->firstName,
                lastName: $identity->lastName,
                displayName: $identity->displayName,
                internalRoleCodes: $roleMapping->internalRoles,
            );

            $security->login($user, LoginFormAuthenticator::class, 'main');

            $this->auditLogger->log(
                action: 'sso.login.success',
                resourceType: 'sso',
                resourceIdentifier: 'office365',
                actor: $user,
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
                context: [
                    'tenant_id' => $identity->tenantId,
                    'subject' => $identity->subject,
                    'email' => $identity->email,
                    'external_roles' => $roleMapping->externalRoles,
                    'internal_roles' => $roleMapping->internalRoles,
                    'matched_external_roles' => $roleMapping->matchedExternalRoles,
                ],
                sensitive: true,
                flush: true,
            );

            return $this->redirectToRoute('app_private_home');
        } catch (SsoAuthenticationException $exception) {
            $this->auditLogger->log(
                action: 'sso.login.failure',
                resourceType: 'sso',
                resourceIdentifier: 'office365',
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
                context: [
                    'reason_code' => $exception->getReasonCode(),
                    'context' => $exception->getContext(),
                ],
                sensitive: true,
                flush: true,
            );

            $this->addFlash(
                'error',
                $this->verboseUserErrors
                    ? sprintf('Échec SSO (%s): %s', $exception->getReasonCode(), $exception->getUserMessage())
                    : 'Connexion SSO impossible. Contactez le support si le problème persiste.'
            );
        } catch (\Throwable $exception) {
            $incidentId = strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));

            $this->auditLogger->log(
                action: 'sso.login.failure_unexpected',
                resourceType: 'sso',
                resourceIdentifier: 'office365',
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent'),
                context: [
                    'incident_id' => $incidentId,
                ],
                sensitive: true,
                flush: true,
            );

            $this->logger->error('sso_unexpected_failure', [
                'incident_id' => $incidentId,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            $this->addFlash(
                'error',
                sprintf('Connexion SSO impossible (incident %s). Contactez le support.', $incidentId)
            );
        }

        return $this->redirectToRoute('app_login');
    }

    private function resetInconsistentAuthenticationState(): void
    {
        $this->tokenStorage->setToken(null);
        $session = $this->requestStack->getSession();
        if ($session !== null) {
            $session->invalidate();
        }
    }
}
