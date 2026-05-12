<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Domain\Access\Entity\Role;
use App\Domain\Access\Entity\User;
use App\Infrastructure\Repository\RoleRepository;
use App\Infrastructure\Repository\UserRepository;
use App\UI\Form\ExternalUserRegistrationType;
use App\UI\Form\Model\ExternalUserRegistrationData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register_external', methods: ['GET', 'POST'])]
    public function registerExternal(
        Request $request,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_private_home');
        }

        $data = new ExternalUserRegistrationData();
        $form = $this->createForm(ExternalUserRegistrationType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($userRepository->findOneBy(['email' => strtolower(trim($data->email))]) instanceof User) {
                $this->addFlash('error', 'Un compte existe déjà avec cet email.');
            } else {
                $roleExternal = $roleRepository->findOneBy(['code' => 'ROLE_EXTERNAL']);
                if (!$roleExternal instanceof Role) {
                    $roleExternal = (new Role('Externe enregistré', 'ROLE_EXTERNAL'))
                        ->setDescription('Accès à l’espace privé des comptes externes');
                    $entityManager->persist($roleExternal);
                }

                $displayName = trim(sprintf('%s %s', $data->firstName, $data->lastName));
                if ($displayName === '') {
                    $displayName = trim((string) ($data->organizationName ?? ''));
                }
                if ($displayName === '') {
                    $displayName = strtolower(trim($data->email));
                }

                $user = (new User())
                    ->setEmail($data->email)
                    ->setUserType($data->userType)
                    ->setFirstName($data->firstName)
                    ->setLastName($data->lastName)
                    ->setOrganizationName($data->organizationName)
                    ->setWebsiteUrl($data->websiteUrl)
                    ->setDisplayName($displayName)
                    ->setAuthProvider(User::AUTH_PROVIDER_LOCAL)
                    ->setIsActive(true)
                    ->addRole($roleExternal);

                $user->setPassword($passwordHasher->hashPassword($user, $data->password));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Compte créé avec succès. Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register_external.html.twig', [
            'form' => $form,
        ]);
    }
}
