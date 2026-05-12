<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Agent\Entity\AgentRequest;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

final class AgentRequestCrudController extends AbstractCrudController
{
    private const STATUS_CHOICES = [
        'Soumise' => 'submitted',
        'En cours' => 'processing',
        'Traitée' => 'processed',
        'Rejetée' => 'rejected',
        'Archivée' => 'archived',
    ];

    private const STATUS_BADGES = [
        'submitted' => 'info',
        'processing' => 'warning',
        'processed' => 'success',
        'rejected' => 'danger',
        'archived' => 'secondary',
    ];

    public static function getEntityFqcn(): string
    {
        return AgentRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de carte')
            ->setEntityLabelInPlural('Demandes de cartes')
            ->setDefaultSort(['submittedAt' => 'DESC'])
            ->setSearchFields(['requestNumber', 'title', 'description', 'requester.email', 'requester.displayName'])
            ->setPaginatorPageSize(30);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action): Action => $action
                ->setLabel('Traiter')
                ->setIcon('fa fa-pen-to-square'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(self::STATUS_CHOICES))
            ->add('requestType')
            ->add('requester')
            ->add('assignedTo');
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_EDIT === $pageName) {
            yield ChoiceField::new('status', 'Statut')
                ->setChoices(self::STATUS_CHOICES)
                ->renderAsBadges(self::STATUS_BADGES)
                ->setColumns(4);
            yield AssociationField::new('assignedTo', 'Assignée à')
                ->autocomplete()
                ->setColumns(4);
            yield DateTimeField::new('processedAt', 'Traitée le')
                ->setColumns(4);

            return;
        }

        yield TextField::new('requestNumber', 'N°');
        yield TextField::new('title', 'Titre');
        yield AssociationField::new('requestType', 'Type');
        yield AssociationField::new('requester', 'Demandeur');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(self::STATUS_CHOICES)
            ->renderAsBadges(self::STATUS_BADGES);
        yield DateTimeField::new('submittedAt', 'Soumise le');
        yield AssociationField::new('assignedTo', 'Assignée à');
        yield Field::new('attachments', 'Pièces jointes')
            ->setTemplatePath('admin/field/agent_request_attachments.html.twig');

        if (Crud::PAGE_DETAIL === $pageName) {
            yield DateTimeField::new('processedAt', 'Traitée le');
            yield TextareaField::new('description', 'Description');
            yield ArrayField::new('payload', 'Données complémentaires');
            yield DateTimeField::new('createdAt', 'Créée le');
            yield DateTimeField::new('updatedAt', 'Mise à jour le');
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof AgentRequest && $entityInstance->getProcessedAt() === null && \in_array($entityInstance->getStatus(), ['processed', 'rejected'], true)) {
            $entityInstance->setProcessedAt(new \DateTimeImmutable());
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
