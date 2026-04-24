<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Content\Service\HomepageQueryService;
use App\Infrastructure\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_home', methods: ['GET'])]
    public function __invoke(HomepageQueryService $homepageQueryService, PageRepository $pageRepository): Response
    {
        $viewModel = $homepageQueryService->buildHomepageViewModel();
        $presentationPage = $pageRepository->findOneBy(['slug' => 'presentation-portail', 'status' => 'published']);

        return $this->render('public/home.html.twig', [
            'presentationPage' => $presentationPage,
            ...$viewModel,
        ]);
    }
}
