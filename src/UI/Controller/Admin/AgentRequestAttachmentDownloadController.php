<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Agent\Entity\AgentRequestAttachment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AgentRequestAttachmentDownloadController extends AbstractController
{
    #[Route('/admin/demandes-cartes/pieces-jointes/{id}/telecharger', name: 'admin_agent_request_attachment_download', methods: ['GET'])]
    public function __invoke(AgentRequestAttachment $attachment): Response
    {
        $uploadRoot = $this->getParameter('kernel.project_dir').'/var/uploads';
        $realUploadRoot = realpath($uploadRoot);
        $realPath = realpath($uploadRoot.'/'.$attachment->getStoragePath());

        if ($realUploadRoot === false || $realPath === false || !str_starts_with($realPath, $realUploadRoot.DIRECTORY_SEPARATOR)) {
            throw $this->createNotFoundException('Pièce jointe introuvable.');
        }

        return $this->file($realPath, $attachment->getOriginalName());
    }
}
