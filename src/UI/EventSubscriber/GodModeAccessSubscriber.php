<?php

declare(strict_types=1);

namespace App\UI\EventSubscriber;

use App\Application\Access\Service\GodModeService;
use App\Domain\Access\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GodModeAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly GodModeService $godModeService,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');
        $path = $request->getPathInfo();

        $user = $this->security->getUser();
        if (!$user instanceof User || !$this->godModeService->isEligible($user)) {
            return;
        }

        if (\in_array($route, ['app_god_mode_profile_switch', 'app_logout'], true)) {
            return;
        }

        if ($this->godModeService->isPublicSimulation($user) && (str_starts_with($path, '/extranet') || str_starts_with($path, '/espace-prive'))) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_home')));

            return;
        }

        if (str_starts_with($path, '/admin')) {
            if ($this->godModeService->hasEffectiveRole($user, 'ROLE_ADMIN')) {
                return;
            }

            $canManageRequestsOnly = $this->godModeService->hasEffectiveRole($user, 'ROLE_MANAGER');
            $allowedManagerRoutes = [
                'admin_dashboard_agent_request_index',
                'admin_dashboard_agent_request_edit',
                'admin_dashboard_agent_request_detail',
                'admin_dashboard_agent_request_render_filters',
                'admin_dashboard_agent_request_autocomplete',
                'admin_agent_request_attachment_download',
            ];

            if ($canManageRequestsOnly && \in_array($route, $allowedManagerRoutes, true)) {
                return;
            }

            if ($canManageRequestsOnly) {
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('admin_dashboard_agent_request_index')));

                return;
            }

            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_private_home')));

            return;
        }

        if (str_starts_with($path, '/agents') && !$this->godModeService->hasEffectiveRole($user, 'ROLE_AGENT')) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_private_home')));
        }
    }
}
