<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Access\Service\CatalogResourceLocator;
use App\Application\Access\Service\GodModeService;
use App\Application\Access\Service\VisibilityScopeResolver;
use App\Domain\Access\Entity\User;
use App\Domain\Access\Entity\UserFavorite;
use App\Domain\Agent\Entity\AgentRequest;
use App\Infrastructure\Repository\AgentRequestRepository;
use App\Infrastructure\Repository\ExternalResourceRequestRepository;
use App\Infrastructure\Repository\UserFavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/extranet', name: 'extranet_')]
#[IsGranted('ROLE_USER')]
final class ExtranetController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET', 'POST'])]
    public function dashboard(
        Request $request,
        UserFavoriteRepository $favoriteRepository,
        ExternalResourceRequestRepository $externalResourceRequestRepository,
        AgentRequestRepository $agentRequestRepository,
        CatalogResourceLocator $catalogResourceLocator,
        VisibilityScopeResolver $visibilityScopeResolver,
        GodModeService $godModeService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($godModeService->isPublicSimulation($user)) {
            return $this->redirectToRoute('app_home');
        }

        $effectiveUserType = $godModeService->getEffectiveUserType($user) ?? $user->getUserType();
        $isAgentExtranet = \in_array($effectiveUserType, User::ssoAccountTypes(), true);
        $allowedScopes = $visibilityScopeResolver->resolveForUser($user);
        $externalRequests = $externalResourceRequestRepository->findLatestForUser($user);
        $agentRequests = $isAgentExtranet ? $agentRequestRepository->findLatestForUser($user) : [];
        $favoriteCards = array_map(
            fn (UserFavorite $favorite): array => $this->buildFavoriteCard($favorite, $catalogResourceLocator, $allowedScopes),
            $favoriteRepository->findLatestForUser($user),
        );

        return $this->render('extranet/dashboard.html.twig', [
            'isAgentExtranet' => $isAgentExtranet,
            'extranetTitle' => $isAgentExtranet ? 'Espace extranet agent' : 'Espace extranet professionnel',
            'requestSummary' => [
                'favorites' => \count($favoriteCards),
                'external' => \count($externalRequests),
                'agent' => \count($agentRequests),
            ],
            'favorites' => $favoriteCards,
            'resourceRequests' => $externalRequests,
            'agentRequests' => array_map(
                fn (AgentRequest $agentRequest): array => $this->buildAgentRequestCard($agentRequest),
                $agentRequests,
            ),
        ]);
    }

    #[Route('/favoris/ajouter/{kind}/{slug}', name: 'favorite_add', methods: ['POST'])]
    public function addFavorite(
        string $kind,
        string $slug,
        Request $request,
        CatalogResourceLocator $catalogResourceLocator,
        VisibilityScopeResolver $visibilityScopeResolver,
        UserFavoriteRepository $favoriteRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $tokenId = sprintf('favorite_add_%s_%s', $kind, $slug);
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('extranet_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();

        $target = $catalogResourceLocator->findFavoriteTarget(
            $kind,
            $slug,
            $visibilityScopeResolver->resolveForUser($user),
        );
        if ($target === null) {
            $this->addFlash('error', 'La ressource demandée est introuvable ou non accessible.');

            return $this->redirectToRoute('extranet_dashboard');
        }

        $existing = $favoriteRepository->findOneForUserAndResource($user, $target['kind'], $target['slug']);
        if (!$existing instanceof UserFavorite) {
            $favorite = (new UserFavorite())
                ->setUser($user)
                ->setResourceKind($target['kind'])
                ->setResourceSlug($target['slug'])
                ->setResourceTitle($target['title'])
                ->setResourceUrl($target['url']);
            $entityManager->persist($favorite);
            $entityManager->flush();
            $this->addFlash('success', 'Ressource ajoutée à vos favoris.');
        } else {
            $this->addFlash('info', 'Cette ressource est déjà dans vos favoris.');
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('extranet_dashboard'));
    }

    #[Route('/favoris/supprimer/{id}', name: 'favorite_remove', methods: ['POST'])]
    public function removeFavorite(
        UserFavorite $favorite,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $tokenId = sprintf('favorite_remove_%d', $favorite->getId() ?? 0);
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('extranet_dashboard');
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($favorite->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès interdit.');
        }

        $entityManager->remove($favorite);
        $entityManager->flush();
        $this->addFlash('success', 'Favori supprimé.');

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('extranet_dashboard'));
    }

    #[Route('/favoris/bascule/{kind}/{slug}', name: 'favorite_toggle', methods: ['POST'])]
    public function toggleFavorite(
        string $kind,
        string $slug,
        Request $request,
        CatalogResourceLocator $catalogResourceLocator,
        VisibilityScopeResolver $visibilityScopeResolver,
        UserFavoriteRepository $favoriteRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $tokenId = sprintf('favorite_toggle_%s_%s', $kind, $slug);
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_csrf_token'))) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Jeton de sécurité invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        /** @var User $user */
        $user = $this->getUser();

        $target = $catalogResourceLocator->findFavoriteTarget(
            $kind,
            $slug,
            $visibilityScopeResolver->resolveForUser($user),
        );
        if ($target === null) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Ressource introuvable ou non accessible.',
            ], Response::HTTP_NOT_FOUND);
        }

        $existing = $favoriteRepository->findOneForUserAndResource($user, $target['kind'], $target['slug']);

        if ($existing instanceof UserFavorite) {
            $entityManager->remove($existing);
            $entityManager->flush();

            return new JsonResponse([
                'ok' => true,
                'isFavorite' => false,
                'message' => 'Favori retiré.',
            ]);
        }

        $favorite = (new UserFavorite())
            ->setUser($user)
            ->setResourceKind($target['kind'])
            ->setResourceSlug($target['slug'])
            ->setResourceTitle($target['title'])
            ->setResourceUrl($target['url']);
        $entityManager->persist($favorite);
        $entityManager->flush();

        return new JsonResponse([
            'ok' => true,
            'isFavorite' => true,
            'message' => 'Favori ajouté.',
        ]);
    }

    /**
     * @param list<string> $allowedScopes
     *
     * @return array{id: int, resourceTitle: string, resourceUrl: string, kindLabel: string, thumbnailUrl: ?string}
     */
    private function buildFavoriteCard(
        UserFavorite $favorite,
        CatalogResourceLocator $catalogResourceLocator,
        array $allowedScopes,
    ): array {
        $resolvedTarget = $catalogResourceLocator->findFavoriteTarget(
            $favorite->getResourceKind(),
            $favorite->getResourceSlug(),
            $allowedScopes,
        );

        $resourceTitle = $favorite->getResourceTitle();
        $resourceUrl = $favorite->getResourceUrl();
        $thumbnailUrl = null;

        if (\is_array($resolvedTarget)) {
            $resourceTitle = trim((string) ($resolvedTarget['title'] ?? '')) ?: $resourceTitle;
            $resourceUrl = trim((string) ($resolvedTarget['url'] ?? '')) ?: $resourceUrl;
            $thumbnailUrl = $this->normalizeFavoriteThumbnailPath(
                $favorite->getResourceKind(),
                isset($resolvedTarget['thumbnailPath']) ? (string) $resolvedTarget['thumbnailPath'] : null,
            );
        }

        return [
            'id' => (int) ($favorite->getId() ?? 0),
            'resourceTitle' => $resourceTitle,
            'resourceUrl' => $resourceUrl,
            'kindLabel' => $favorite->getKindLabel(),
            'thumbnailUrl' => $thumbnailUrl,
        ];
    }

    /**
     * @return array{
     *     requestNumber: string,
     *     title: string,
     *     status: string,
     *     submittedAt: \DateTimeImmutable,
     *     urgency: string,
     *     geographicArea: string,
     *     requestKindLabel: string,
     *     destinationLabel: string
     * }
     */
    private function buildAgentRequestCard(AgentRequest $agentRequest): array
    {
        $payload = $agentRequest->getPayload() ?? [];
        $requestKinds = isset($payload['requestKinds']) && \is_array($payload['requestKinds']) ? $payload['requestKinds'] : [];
        $requestKindLabel = match (true) {
            \in_array('map', $requestKinds, true) && \in_array('data', $requestKinds, true) => 'Carte + données',
            \in_array('map', $requestKinds, true) => 'Carte',
            \in_array('data', $requestKinds, true) => 'Données',
            default => 'Non précisé',
        };

        $urgency = trim((string) ($payload['urgencyLevel'] ?? 'normal'));
        $urgencyLabel = match ($urgency) {
            'urgent' => 'Urgent',
            'very_urgent' => 'Très urgent',
            default => 'Normal',
        };

        $destination = trim((string) ($payload['deliveryDestination'] ?? 'internal'));
        $destinationLabel = $destination === 'external' ? 'Diffusion externe' : 'Usage interne';

        return [
            'requestNumber' => $agentRequest->getRequestNumber(),
            'title' => $agentRequest->getTitle(),
            'status' => $agentRequest->getStatus(),
            'submittedAt' => $agentRequest->getSubmittedAt(),
            'urgency' => $urgencyLabel,
            'geographicArea' => trim((string) ($payload['geographicArea'] ?? '')),
            'requestKindLabel' => $requestKindLabel,
            'destinationLabel' => $destinationLabel,
        ];
    }

    private function normalizeFavoriteThumbnailPath(string $resourceKind, ?string $thumbnailPath): ?string
    {
        $candidate = trim((string) $thumbnailPath);
        if ($candidate === '') {
            return null;
        }

        if (
            str_starts_with($candidate, 'http://')
            || str_starts_with($candidate, 'https://')
            || str_starts_with($candidate, '/')
        ) {
            return $candidate;
        }

        if (str_starts_with($candidate, 'uploads/')) {
            return '/'.ltrim($candidate, '/');
        }

        if (\in_array($resourceKind, [UserFavorite::KIND_DATA_SOURCE, UserFavorite::KIND_STATIC_MAP], true)) {
            return '/uploads/data-sources/'.ltrim($candidate, '/');
        }

        return null;
    }
}
