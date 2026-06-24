<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Infrastructure\Repository\NewsRepository;
use App\Infrastructure\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '', name: 'app_content_')]
final class ContentController extends AbstractController
{
    #[Route('/actualites', name: 'news_index', methods: ['GET'])]
    public function newsIndex(NewsRepository $newsRepository): Response
    {
        $news = $newsRepository->findBy(['status' => 'published'], ['publishedAt' => 'DESC'], 30);

        return $this->render('public/news/index.html.twig', [
            'news' => $news,
        ]);
    }

    #[Route('/actualites/{slug}', name: 'news_show', methods: ['GET'])]
    public function newsShow(string $slug, NewsRepository $newsRepository): Response
    {
        $article = $newsRepository->findOneBy(['slug' => $slug, 'status' => 'published']);
        if ($article === null) {
            throw $this->createNotFoundException('Actualité introuvable.');
        }

        return $this->render('public/news/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/pages/{slug}', name: 'page_show', methods: ['GET'])]
    public function pageShow(string $slug, PageRepository $pageRepository): Response
    {
        $page = $pageRepository->findOneBy(['slug' => $slug, 'status' => 'published']);
        if ($page === null) {
            throw $this->createNotFoundException('Page introuvable.');
        }

        return $this->render('public/page/show.html.twig', [
            'page' => $page,
        ]);
    }

    #[Route('/thematiques', name: 'themes', methods: ['GET'])]
    public function themes(): Response
    {
        return $this->render('public/page/themes.html.twig');
    }

    #[Route('/sig-innova', name: 'sig_innova', methods: ['GET'])]
    public function sigInnova(PageRepository $pageRepository): Response
    {
        return $this->render('public/page/show.html.twig', [
            'page' => $pageRepository->findOneBy(['slug' => 'sig-innova', 'status' => 'published']),
            'fallbackTitle' => 'SIG-Innova',
        ]);
    }

    #[Route('/partenaires', name: 'partners_hub', methods: ['GET'])]
    public function partnersHub(PageRepository $pageRepository): Response
    {
        return $this->render('public/page/show.html.twig', [
            'page' => $pageRepository->findOneBy(['slug' => 'partenaires', 'status' => 'published']),
            'fallbackTitle' => 'Partenaires',
        ]);
    }

    #[Route('/mentions-legales', name: 'legal_mentions', methods: ['GET'])]
    public function legalMentions(PageRepository $pageRepository): Response
    {
        $page = $pageRepository->findOneBy(['slug' => 'mentions-legales', 'status' => 'published']);

        return $this->render('public/page/show.html.twig', [
            'page' => $page,
            'fallbackTitle' => 'Mentions légales',
        ]);
    }

    #[Route('/politique-confidentialite', name: 'legal_privacy', methods: ['GET'])]
    public function privacy(PageRepository $pageRepository): Response
    {
        $page = $pageRepository->findOneBy(['slug' => 'politique-confidentialite', 'status' => 'published']);

        return $this->render('public/page/show.html.twig', [
            'page' => $page,
            'fallbackTitle' => 'Politique de confidentialité',
        ]);
    }

    #[Route('/cookies', name: 'legal_cookies', methods: ['GET'])]
    public function cookies(PageRepository $pageRepository): Response
    {
        $page = $pageRepository->findOneBy(['slug' => 'politique-cookies', 'status' => 'published']);

        return $this->render('public/page/show.html.twig', [
            'page' => $page,
            'fallbackTitle' => 'Politique de cookies',
        ]);
    }

    #[Route('/accessibilite', name: 'legal_accessibility', methods: ['GET'])]
    public function accessibility(PageRepository $pageRepository): Response
    {
        $page = $pageRepository->findOneBy(['slug' => 'declaration-accessibilite', 'status' => 'published']);

        return $this->render('public/page/show.html.twig', [
            'page' => $page,
            'fallbackTitle' => 'Accessibilité',
        ]);
    }

    #[Route('/partenaires-financeurs', name: 'partners', methods: ['GET'])]
    public function partners(): Response
    {
        return $this->render('public/page/partners.html.twig');
    }
}
