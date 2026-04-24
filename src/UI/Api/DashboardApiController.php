<?php

declare(strict_types=1);

namespace App\UI\Api;

use App\Infrastructure\Repository\DashboardMetricSnapshotRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/dashboard', name: 'api_dashboard_')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardApiController extends AbstractController
{
    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(DashboardMetricSnapshotRepository $repository): JsonResponse
    {
        $snapshots = $repository->findBy([], ['recordedAt' => 'DESC'], 20);

        $data = [];
        foreach ($snapshots as $snapshot) {
            $data[] = [
                'metricKey' => $snapshot->getMetricKey(),
                'scope' => $snapshot->getScope(),
                'recordedAt' => $snapshot->getRecordedAt()->format(DATE_ATOM),
                'valueNumeric' => $snapshot->getValueNumeric(),
                'valueInteger' => $snapshot->getValueInteger(),
            ];
        }

        return $this->json([
            'metrics' => $data,
        ]);
    }
}
