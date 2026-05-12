<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Access\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserCrudController extends AbstractCrudController
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield EmailField::new('email');
        yield ChoiceField::new('userType', 'Type de compte')
            ->setChoices([
                'Comptes locaux' => [
                    User::TYPE_LABELS[User::TYPE_ADMIN_EXTERNAL] => User::TYPE_ADMIN_EXTERNAL,
                    User::TYPE_LABELS[User::TYPE_EXTERNAL] => User::TYPE_EXTERNAL,
                ],
                'Comptes SSO' => [
                    User::TYPE_LABELS[User::TYPE_ADMIN_SSO] => User::TYPE_ADMIN_SSO,
                    User::TYPE_LABELS[User::TYPE_AGENT_SSO] => User::TYPE_AGENT_SSO,
                ],
            ]);
        yield TextField::new('firstName', 'Prénom')->hideOnIndex();
        yield TextField::new('lastName', 'Nom')->hideOnIndex();
        yield TextField::new('organizationName', 'Organisation')->hideOnIndex();
        yield TextField::new('websiteUrl', 'Site web')->hideOnIndex();
        yield TextField::new('displayName');
        yield TextField::new('authProvider', 'Authentification')
            ->hideOnForm();
        yield TextField::new('ssoSubject', 'Identifiant SSO')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('Obligatoire uniquement pour les types de compte SSO.');
        yield TextField::new('password')
            ->onlyOnForms()
            ->setRequired(false)
            ->setHelp('Comptes locaux: mot de passe local. Comptes SSO: laisser vide.');
        yield BooleanField::new('isActive');
        yield AssociationField::new('roleEntities', 'Rôles')
            ->setFormTypeOption('by_reference', false)
            ->autocomplete()
            ->hideOnIndex();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->synchronizeAuthenticationSettings($entityInstance);
            $this->hashPasswordIfNeeded($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->synchronizeAuthenticationSettings($entityInstance);
            $this->hashPasswordIfNeeded($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPasswordIfNeeded(User $user): void
    {
        $password = $user->getPassword();
        if ($password === '') {
            return;
        }

        if (str_starts_with($password, '$2') || str_starts_with($password, '$argon2')) {
            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
    }

    private function synchronizeAuthenticationSettings(User $user): void
    {
        $expectedProvider = User::expectedAuthProviderForType($user->getUserType());
        $user->setAuthProvider($expectedProvider);

        if ($expectedProvider === User::AUTH_PROVIDER_LOCAL) {
            $user->setSsoSubject(null);
        }
    }
}
