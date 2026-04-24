<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Domain\Access\Entity\User;
use App\Domain\Content\Entity\ContactMessage;
use App\Infrastructure\Logging\AuditLogger;
use App\UI\Form\ContactMessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        AuditLogger $auditLogger,
    ): Response {
        $contactMessage = new ContactMessage();
        $form = $this->createForm(ContactMessageType::class, $contactMessage, [
            'action' => $this->generateUrl('app_contact'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactMessage->setIpAddress($request->getClientIp());
            $entityManager->persist($contactMessage);

            $auditLogger->log(
                action: 'contact_message.created',
                resourceType: 'contact_message',
                resourceIdentifier: (string) $contactMessage->getUuid(),
                actor: $this->getUser() instanceof User ? $this->getUser() : null,
                ipAddress: $request->getClientIp(),
                userAgent: $request->headers->get('User-Agent')
            );

            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['ok' => true, 'message' => 'Message envoye avec succes.']);
            }

            $this->addFlash('success', 'Votre message a ete transmis.');

            return $this->redirectToRoute('app_contact');
        }

        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            return new JsonResponse([
                'ok' => false,
                'errors' => $this->extractFormErrors($form->getErrors(true)),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('public/contact.html.twig', [
            'form' => $form,
        ]);
    }

    /** @return list<string> */
    private function extractFormErrors(FormErrorIterator $errors): array
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getMessage();
        }

        return array_values(array_unique($messages));
    }
}
