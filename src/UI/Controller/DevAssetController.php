<?php

declare(strict_types=1);

namespace App\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

final class DevAssetController extends AbstractController
{
    #[Route('/_dev/assets/styles/{file}', name: 'app_dev_asset_style', requirements: ['file' => '[A-Za-z0-9_.-]+\.css'], methods: ['GET'])]
    public function style(string $file, KernelInterface $kernel): Response
    {
        if (!$kernel->isDebug()) {
            throw $this->createNotFoundException();
        }

        $path = $kernel->getProjectDir().'/assets/styles/'.$file;
        if (!is_file($path)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', 'text/css; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
