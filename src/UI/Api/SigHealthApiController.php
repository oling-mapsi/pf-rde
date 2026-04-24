<?php

declare(strict_types=1);

namespace App\UI\Api;

use App\Application\Interop\Sig\SigHealthcheckService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sig', name: 'api_sig_')]
final class SigHealthApiController extends AbstractController
{
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(SigHealthcheckService $service): JsonResponse
    {
        $data = [];
        foreach ($service->checkAll() as $check) {
            $data[] = [
                'name' => $check->name,
                'serviceType' => $check->serviceType,
                'available' => $check->available,
                'message' => $check->message,
            ];
        }

        return $this->json([
            'services' => $data,
            'degradedMode' => count(array_filter($data, static fn (array $service): bool => $service['available'] === false)) > 0,
        ]);
    }
}
