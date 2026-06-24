<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Content\Entity\HomepageContent;
use App\Domain\Content\Entity\HomepageSection;
use App\Infrastructure\Repository\HomepageContentRepository;
use App\Infrastructure\Repository\HomepageSectionRepository;
use App\Infrastructure\Repository\TaxonomyTermRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class HomepageBuilderController extends AbstractController
{
    #[Route('/admin/page-accueil/builder', name: 'admin_homepage_builder', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        HomepageContentRepository $homepageContentRepository,
        HomepageSectionRepository $homepageSectionRepository,
        TaxonomyTermRepository $taxonomyTermRepository,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator,
    ): Response {
        $homepage = $homepageContentRepository->findEditableHomepage();
        $sections = $homepageSectionRepository->findAllOrdered();
        $featuredThemes = $taxonomyTermRepository->findFeaturedMapThemes(7);

        if ($request->isMethod('POST')) {
            $form = (string) $request->request->get('_form', 'hero');

            if ($form === 'section_cards') {
                $this->validateCsrf((string) $request->request->get('_token'), 'homepage_section_cards');
                $submittedSections = $request->request->all('sections');

                foreach ($sections as $section) {
                    if (!$this->isEditableCardsSection($section)) {
                        continue;
                    }

                    $sectionPayload = \is_array($submittedSections[(string) $section->getId()] ?? null)
                        ? $submittedSections[(string) $section->getId()]
                        : [];

                    $this->hydrateManualCardsSection($section, $sectionPayload);
                }

                $entityManager->flush();
                $this->addFlash('success', 'Les images et cartes des sections ont été mises à jour.');

                return $this->redirect($this->builderUrl($adminUrlGenerator));
            }

            if ($form === 'hero') {
                $this->validateCsrf((string) $request->request->get('_token'), 'homepage_builder');
                $this->hydrateHomepage($homepage, $request);

                if ($homepage->getId() === null) {
                    $entityManager->persist($homepage);
                }

                $entityManager->flush();
                $this->addFlash('success', 'Le héros de la page d’accueil a été mis à jour.');

                return $this->redirect($this->builderUrl($adminUrlGenerator));
            }

            throw $this->createNotFoundException('Formulaire inconnu.');
        }

        $editableCardsSections = array_values(array_filter(
            $sections,
            fn (HomepageSection $section): bool => $this->isEditableCardsSection($section),
        ));

        $newSectionUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(HomepageSectionCrudController::class)
            ->setAction(Action::NEW)
            ->generateUrl();

        $heroCrudUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(HomepageContentCrudController::class)
            ->setAction($homepage->getId() === null ? Action::NEW : Action::EDIT)
            ->setEntityId($homepage->getId())
            ->generateUrl();

        return $this->render('admin/homepage_builder.html.twig', [
            'homepage' => $homepage,
            'sections' => $sections,
            'editableCardsSections' => $editableCardsSections,
            'featuredThemes' => $featuredThemes,
            'newSectionUrl' => $newSectionUrl,
            'heroCrudUrl' => $heroCrudUrl,
            'themeCrudController' => MapThemeCrudController::class,
            'sectionCrudController' => HomepageSectionCrudController::class,
            'reorderUrl' => $this->generateUrl('admin_homepage_sections_reorder'),
        ]);
    }

    #[Route('/admin/page-accueil/sections/reorder', name: 'admin_homepage_sections_reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        HomepageSectionRepository $homepageSectionRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $payload = json_decode((string) $request->getContent(), true);
        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $this->validateCsrf((string) ($payload['_token'] ?? ''), 'homepage_reorder');
        $positions = $payload['positions'] ?? [];
        if (!\is_array($positions)) {
            return new JsonResponse(['error' => 'Positions invalides.'], Response::HTTP_BAD_REQUEST);
        }

        $rank = 10;
        foreach ($positions as $id) {
            $section = $homepageSectionRepository->find((int) $id);
            if ($section === null) {
                continue;
            }

            $section->setPosition($rank);
            $rank += 10;
        }

        $entityManager->flush();

        return new JsonResponse(['status' => 'ok']);
    }

    private function hydrateHomepage(HomepageContent $homepage, Request $request): void
    {
        $homepage
            ->setName(trim((string) $request->request->get('name', 'Accueil principal')))
            ->setHeroTitle(trim((string) $request->request->get('heroTitle', '')))
            ->setHeroBaseline($this->nullIfBlank($request->request->get('heroBaseline')))
            ->setSearchIntro($this->nullIfBlank($request->request->get('searchIntro')))
            ->setSearchPlaceholder($this->nullIfBlank($request->request->get('searchPlaceholder')))
            ->setPrimaryCtaLabel($this->nullIfBlank($request->request->get('primaryCtaLabel')))
            ->setPrimaryCtaUrl($this->nullIfBlank($request->request->get('primaryCtaUrl')))
            ->setStatus('published')
            ->setPublishedAt(new \DateTimeImmutable());
    }

    private function builderUrl(AdminUrlGenerator $adminUrlGenerator): string
    {
        return $adminUrlGenerator
            ->unsetAll()
            ->setRoute('admin_homepage_builder')
            ->generateUrl();
    }

    private function isEditableCardsSection(HomepageSection $section): bool
    {
        return ($section->getType() === HomepageSection::TYPE_MANUAL_CARDS && $section->getBackgroundStyle() !== 'kpi')
            || $section->getType() === HomepageSection::TYPE_REQUEST_GATEWAY;
    }

    /** @param array<string, mixed> $sectionPayload */
    private function hydrateManualCardsSection(HomepageSection $section, array $sectionPayload): void
    {
        $itemsPayload = \is_array($sectionPayload['items'] ?? null) ? $sectionPayload['items'] : [];
        $currentItems = $section->getItemsConfigArray();
        $normalizedItems = [];

        foreach ($itemsPayload as $index => $itemPayload) {
            if (!\is_array($itemPayload)) {
                continue;
            }

            $existing = \is_array($currentItems[(int) $index] ?? null) ? $currentItems[(int) $index] : [];
            $title = trim((string) ($itemPayload['title'] ?? ''));
            $text = trim((string) ($itemPayload['text'] ?? ''));
            $imagePath = trim((string) ($itemPayload['imagePath'] ?? ''));
            $url = trim((string) ($itemPayload['url'] ?? ''));
            $label = trim((string) ($itemPayload['label'] ?? ''));

            if ($title === '' && $text === '' && $imagePath === '' && $url === '' && $label === '') {
                continue;
            }

            $normalized = [];

            if ($title !== '') {
                $normalized['title'] = $title;
            }
            if ($text !== '') {
                $normalized['text'] = $text;
            }
            if ($imagePath !== '') {
                $normalized['imagePath'] = $imagePath;
            }
            if ($url !== '') {
                $normalized['url'] = $url;
            }
            if ($label !== '') {
                $normalized['label'] = $label;
            }

            foreach (['accent', 'icon', 'color', 'external'] as $preservedKey) {
                if (\array_key_exists($preservedKey, $existing) && !\array_key_exists($preservedKey, $normalized)) {
                    $normalized[$preservedKey] = $existing[$preservedKey];
                }
            }

            $normalizedItems[] = $normalized;
        }

        if ($normalizedItems === []) {
            $normalizedItems = $currentItems;
        }

        $section
            ->setItemsConfig(json_encode($normalizedItems, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]')
            ->setImagePath($this->nullIfBlank($sectionPayload['imagePath'] ?? null))
            ->setStatus('published')
            ->setPublishedAt($section->getPublishedAt() ?? new \DateTimeImmutable());
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function validateCsrf(string $token, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
