<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class PageContentImageUploadController extends AbstractController
{
    private const MAX_FILE_SIZE = 5_242_880;
    private const UPLOAD_DIRECTORY = '/uploads/content';

    #[Route('/admin/pages/content-images', name: 'admin_page_content_image_upload', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) $request->headers->get('X-CSRF-TOKEN', '');
        if (!$this->isCsrfTokenValid('page_content_image_upload', $token)) {
            return new JsonResponse(['error' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Aucun fichier image reçu.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$file->isValid()) {
            return new JsonResponse(['error' => 'Le fichier envoyé est invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() !== null && $file->getSize() > self::MAX_FILE_SIZE) {
            return new JsonResponse(['error' => 'L’image dépasse la taille maximale de 5 Mo.'], Response::HTTP_BAD_REQUEST);
        }

        $mimeType = (string) $file->getMimeType();
        if (!\in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            return new JsonResponse(['error' => 'Formats acceptés : JPG, PNG, WebP ou GIF.'], Response::HTTP_BAD_REQUEST);
        }

        $publicDirectory = $this->getParameter('kernel.project_dir') . '/public' . self::UPLOAD_DIRECTORY;
        if (!is_dir($publicDirectory) && !mkdir($publicDirectory, 0775, true) && !is_dir($publicDirectory)) {
            return new JsonResponse(['error' => 'Le dossier d’upload ne peut pas être créé.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $baseName) ?: 'image';
        $baseName = trim($baseName, '-_') ?: 'image';
        $extension = $file->guessExtension() ?: $this->extensionFromMimeType($mimeType);
        $filename = mb_strtolower($baseName) . '-' . bin2hex(random_bytes(6)) . '.' . $extension;

        try {
            $file->move($publicDirectory, $filename);
        } catch (FileException) {
            return new JsonResponse(['error' => 'Impossible d’enregistrer l’image.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $url = self::UPLOAD_DIRECTORY . '/' . $filename;

        return new JsonResponse([
            'url' => $url,
            'href' => $url,
        ], Response::HTTP_CREATED);
    }

    private function extensionFromMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }
}
