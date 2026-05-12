<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Access\Service\CatalogResourceLocator;
use App\Application\Access\Service\VisibilityScopeResolver;
use App\Domain\Access\Entity\ExternalResourceRequest;
use App\Domain\Access\Entity\User;
use App\Domain\Access\Entity\UserFavorite;
use App\Infrastructure\Repository\ExternalResourceRequestRepository;
use App\Infrastructure\Repository\UserFavoriteRepository;
use App\UI\Form\ExternalResourceRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/extranet', name: 'extranet_')]
#[IsGranted('ROLE_EXTERNAL')]
final class ExtranetController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET', 'POST'])]
    public function dashboard(
        Request $request,
        EntityManagerInterface $entityManager,
        UserFavoriteRepository $favoriteRepository,
        ExternalResourceRequestRepository $externalResourceRequestRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $resourceRequest = (new ExternalResourceRequest())
            ->setRequester($user);
        $form = $this->createForm(ExternalResourceRequestType::class, $resourceRequest, [
            'action' => $this->generateUrl('extranet_dashboard'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($resourceRequest);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de ressource a été transmise.');

            return $this->redirectToRoute('extranet_dashboard');
        }

        return $this->render('extranet/dashboard.html.twig', [
            'favorites' => $favoriteRepository->findLatestForUser($user),
            'resourceRequests' => $externalResourceRequestRepository->findLatestForUser($user),
            'requestForm' => $form,
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
}

