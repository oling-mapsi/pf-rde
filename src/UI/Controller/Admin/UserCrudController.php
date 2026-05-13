<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Access\Entity\User;
use App\Infrastructure\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserCrudController extends AbstractCrudController
{
    private const GOD_ROLE_CODE = 'ROLE_GOD';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
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
                    User::TYPE_LABELS[User::TYPE_MANAGER_SSO] => User::TYPE_MANAGER_SSO,
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
        yield AssociationField::new('roles', 'Rôles')
            ->setFormTypeOption('property_path', 'roleEntities')
            ->setFormTypeOption('by_reference', false)
            ->setFormTypeOption('query_builder', static fn (RoleRepository $roleRepository): QueryBuilder => $roleRepository
                ->createQueryBuilder('role')
                ->andWhere('role.code != :godRole')
                ->setParameter('godRole', self::GOD_ROLE_CODE)
                ->orderBy('role.label', 'ASC'))
            ->autocomplete()
            ->hideOnIndex();
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $rootAliases = $queryBuilder->getRootAliases();
        $alias = $rootAliases[0] ?? 'entity';

        $subQueryBuilder = $queryBuilder->getEntityManager()->createQueryBuilder();
        $subQueryBuilder
            ->select('1')
            ->from(User::class, 'godUser')
            ->innerJoin('godUser.roles', 'godRole')
            ->where(sprintf('godUser = %s', $alias))
            ->andWhere('godRole.code = :godRoleCode');

        return $queryBuilder
            ->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($subQueryBuilder->getDQL())))
            ->setParameter('godRoleCode', self::GOD_ROLE_CODE);
    }

    public function detail(AdminContext $context): KeyValueStore|Response
    {
        if ($this->isGodAccount($context->getEntity()->getInstance())) {
            return $this->redirectToUserIndex();
        }

        return parent::detail($context);
    }

    public function edit(AdminContext $context): KeyValueStore|Response
    {
        if ($this->isGodAccount($context->getEntity()->getInstance())) {
            return $this->redirectToUserIndex();
        }

        return parent::edit($context);
    }

    public function delete(AdminContext $context): KeyValueStore|Response
    {
        if ($this->isGodAccount($context->getEntity()->getInstance())) {
            return $this->redirectToUserIndex();
        }

        return parent::delete($context);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->stripGodRole($entityInstance);
            $this->synchronizeAuthenticationSettings($entityInstance);
            $this->hashPasswordIfNeeded($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            if ($this->isGodAccount($entityInstance)) {
                return;
            }

            $this->stripGodRole($entityInstance);
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

    private function isGodAccount(mixed $entityInstance): bool
    {
        if (!$entityInstance instanceof User) {
            return false;
        }

        return \in_array(self::GOD_ROLE_CODE, $entityInstance->getRoles(), true);
    }

    private function redirectToUserIndex(): RedirectResponse
    {
        $this->addFlash('error', 'Compte protégé: modification non autorisée depuis l’admin.');

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction('index')
            ->generateUrl());
    }

    private function stripGodRole(User $user): void
    {
        foreach ($user->getRoleEntities() as $role) {
            if ($role->getCode() === self::GOD_ROLE_CODE) {
                $user->removeRole($role);
            }
        }
    }
}
