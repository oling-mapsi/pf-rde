<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Cartography\DTO\DataCatalogSearchCriteria;
use App\Application\Cartography\Service\DataCatalogService;
use App\Application\Interop\Sig\SigHealthcheckService;
use App\Infrastructure\Repository\InteractiveMapRepository;
use App\Infrastructure\Repository\StaticMapRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartographyController extends AbstractController
{
    #[Route('/donnees-cartes', name: 'app_data_catalog', methods: ['GET'])]
    #[Route('/cartotheque', name: 'app_static_map_catalog', methods: ['GET'])]
    #[Route('/cartes-interactives', name: 'app_interactive_map_catalog', methods: ['GET'])]
    public function dataCatalog(Request $request, DataCatalogService $catalogService): Response
    {
        $criteria = DataCatalogSearchCriteria::fromRequest($request);
        $catalog = $catalogService->search($criteria);

        if ($request->isXmlHttpRequest() || $request->query->getBoolean('partial')) {
            return $this->render('public/catalog/_results.html.twig', [
                'catalog' => $catalog,
                'criteria' => $criteria,
            ]);
        }

        return $this->render('public/catalog/index.html.twig', [
            'catalog' => $catalog,
            'criteria' => $criteria,
        ]);
    }

    #[Route('/cartotheque/{slug}', name: 'app_static_map_show', methods: ['GET'])]
    public function staticShow(string $slug, StaticMapRepository $repository): Response
    {
        $map = $repository->findOneBy(['slug' => $slug, 'status' => 'published']);
        if ($map === null) {
            throw $this->createNotFoundException('Carte introuvable.');
        }

        return $this->render('public/cartography/show.html.twig', [
            'map' => $map,
        ]);
    }

    #[Route('/cartes-interactives/{slug}', name: 'app_interactive_map_show', methods: ['GET'])]
    public function interactiveShow(
        string $slug,
        InteractiveMapRepository $repository,
        SigHealthcheckService $sigHealthcheckService,
    ): Response {
        $map = $repository->findOneBy(['slug' => $slug, 'status' => 'published']);
        if ($map === null) {
            throw $this->createNotFoundException('Carte interactive introuvable.');
        }

        $health = $sigHealthcheckService->checkAll();
        $isDegradedMode = false;
        foreach ($health as $check) {
            if ($check->available === false) {
                $isDegradedMode = true;
                break;
            }
        }

        return $this->render('public/interactive/show.html.twig', [
            'map' => $map,
            'healthchecks' => $health,
            'degradedMode' => $isDegradedMode,
        ]);
    }
}
