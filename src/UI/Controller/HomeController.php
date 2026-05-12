<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Content\Service\HomepageQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_home', methods: ['GET'])]
    public function __invoke(HomepageQueryService $homepageQueryService): Response
    {
        $viewModel = $homepageQueryService->buildHomepageViewModel();

        return $this->render('public/home.html.twig', [
            ...$viewModel,
        ]);
    }
}
