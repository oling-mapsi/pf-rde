<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\Application\Access\Service\SireneSiretVerifier;
use App\Domain\Access\Entity\Role;
use App\Domain\Access\Entity\User;
use App\Infrastructure\Repository\RoleRepository;
use App\Infrastructure\Repository\UserRepository;
use App\UI\Form\ExternalUserRegistrationType;
use App\UI\Form\Model\ExternalUserRegistrationData;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(string:APP_REGISTRATION_FROM_EMAIL)%')]
        private readonly string $registrationFromEmail,
    ) {
    }

    #[Route('/inscription', name: 'app_register_external', methods: ['GET', 'POST'])]
    public function registerExternal(
        Request $request,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SireneSiretVerifier $sireneSiretVerifier,
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
                $siretValidation = $sireneSiretVerifier->validate($data->companySiret);
                if (!$siretValidation->valid) {
                    $form->get('companySiret')->addError(new \Symfony\Component\Form\FormError(
                        $siretValidation->errorMessage ?? 'SIRET invalide.'
                    ));

                    return $this->render('security/register_external.html.twig', [
                        'form' => $form,
                    ]);
                }

                $roleExternal = $roleRepository->findOneBy(['code' => 'ROLE_EXTERNAL']);
                if (!$roleExternal instanceof Role) {
                    $roleExternal = (new Role('Externe enregistré', 'ROLE_EXTERNAL'))
                        ->setDescription('Accès à l’espace privé des comptes externes');
                    $entityManager->persist($roleExternal);
                }

                $displayName = trim(sprintf('%s %s', $data->firstName, $data->lastName));
                if ($displayName === '') {
                    $displayName = strtolower(trim($data->email));
                }

                $verificationToken = bin2hex(random_bytes(32));

                $user = (new User())
                    ->setEmail($data->email)
                    ->setUserType(User::TYPE_EXTERNAL)
                    ->setFirstName($data->firstName)
                    ->setLastName($data->lastName)
                    ->setOrganizationName($data->organizationName)
                    ->setCompanySiret($siretValidation->normalizedSiret)
                    ->setWebsiteUrl($data->websiteUrl)
                    ->setPostalAddress($data->postalAddress)
                    ->setAccountRequestReason($data->accountRequestReason)
                    ->setDisplayName($displayName)
                    ->setAuthProvider(User::AUTH_PROVIDER_LOCAL)
                    ->setIsActive(false)
                    ->setEmailVerificationTokenHash(hash('sha256', $verificationToken))
                    ->setEmailVerifiedAt(null)
                    ->addRole($roleExternal);

                $user->setPassword($passwordHasher->hashPassword($user, $data->password));

                $entityManager->persist($user);
                $entityManager->flush();

                try {
                    $this->sendConfirmationEmail($user, $verificationToken);
                    $this->addFlash(
                        'success',
                        'Compte créé. Vérifiez votre email et cliquez sur le lien de confirmation pour activer votre accès.'
                    );
                } catch (\Throwable $exception) {
                    $this->logger->error('External registration confirmation email failed', [
                        'email' => $user->getEmail(),
                        'message' => $exception->getMessage(),
                    ]);

                    $this->addFlash(
                        'error',
                        'Compte créé mais email de confirmation non envoyé. Contactez le support pour activer votre compte.'
                    );
                }

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register_external.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/inscription/confirmation/{token}', name: 'app_register_external_confirm', methods: ['GET'])]
    public function confirmEmail(string $token, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $token = trim($token);
        if ($token === '') {
            $this->addFlash('error', 'Lien de confirmation invalide.');

            return $this->redirectToRoute('app_login');
        }

        $tokenHash = hash('sha256', $token);
        $user = $userRepository->findOneByEmailVerificationTokenHash($tokenHash);
        if (!$user instanceof User) {
            $this->addFlash('error', 'Le lien de confirmation est invalide ou a expiré.');

            return $this->redirectToRoute('app_login');
        }

        if ($user->isEmailVerified()) {
            $this->addFlash('info', 'Votre email est déjà confirmé. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        $user
            ->setEmailVerifiedAt(new \DateTimeImmutable())
            ->setEmailVerificationTokenHash(null)
            ->setIsActive(true);

        $entityManager->flush();

        $this->addFlash('success', 'Votre compte est maintenant activé. Vous pouvez vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    private function sendConfirmationEmail(User $user, string $rawToken): void
    {
        $confirmationUrl = $this->generateUrl('app_register_external_confirm', [
            'token' => $rawToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from(new Address($this->registrationFromEmail, 'Routes de Guadeloupe'))
            ->to(new Address($user->getEmail(), $user->getDisplayName()))
            ->subject('Confirmez votre compte externe professionnel')
            ->htmlTemplate('emails/external_registration_confirmation.html.twig')
            ->textTemplate('emails/external_registration_confirmation.txt.twig')
            ->context([
                'user' => $user,
                'confirmationUrl' => $confirmationUrl,
            ]);

        $this->mailer->send($email);
    }
}
