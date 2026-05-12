<?php

declare(strict_types=1);

namespace App\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class PublicFileController extends AbstractController
{
    #[Route('/files/{path}', name: 'app_public_file_download', requirements: ['path' => '.+'], methods: ['GET'])]
    public function download(string $path): BinaryFileResponse
    {
        $normalizedPath = ltrim(trim($path), '/');
        if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $publicDir = $projectDir.'/public';

        $candidates = [
            $publicDir.'/files/'.$normalizedPath,
            $publicDir.'/uploads/'.$normalizedPath,
            $publicDir.'/uploads/data-sources/'.basename($normalizedPath),
        ];

        foreach ($candidates as $candidate) {
            if (!is_file($candidate) || !is_readable($candidate)) {
                continue;
            }

            $response = new BinaryFileResponse($candidate);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($candidate));

            return $response;
        }

        throw $this->createNotFoundException('Fichier introuvable ou non publié.');
    }
}

