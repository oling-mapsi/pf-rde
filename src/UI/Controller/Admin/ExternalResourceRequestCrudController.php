<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Application\Notification\RequestNotificationService;
use App\Domain\Access\Entity\ExternalResourceRequest;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

final class ExternalResourceRequestCrudController extends AbstractCrudController
{
    public function __construct(private readonly RequestNotificationService $requestNotificationService)
    {
    }

    private const STATUS_BADGES = [
        ExternalResourceRequest::STATUS_SUBMITTED => 'info',
        ExternalResourceRequest::STATUS_ACKNOWLEDGED => 'primary',
        ExternalResourceRequest::STATUS_IN_REVIEW => 'warning',
        ExternalResourceRequest::STATUS_PROCESSING => 'warning',
        ExternalResourceRequest::STATUS_ON_HOLD => 'secondary',
        ExternalResourceRequest::STATUS_PROCESSED => 'success',
        ExternalResourceRequest::STATUS_REJECTED => 'danger',
        ExternalResourceRequest::STATUS_ARCHIVED => 'secondary',
    ];

    public static function getEntityFqcn(): string
    {
        return ExternalResourceRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande externe')
            ->setEntityLabelInPlural('Demandes externes')
            ->setDefaultSort(['submittedAt' => 'DESC'])
            ->setSearchFields([
                'requestNumber',
                'subject',
                'message',
                'email',
                'firstName',
                'lastName',
                'organizationName',
                'companySiret',
            ])
            ->setPaginatorPageSize(30);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action): Action => $action
                ->setLabel('Instruire')
                ->setIcon('fa fa-pen-to-square'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(array_flip(ExternalResourceRequest::STATUS_LABELS)))
            ->add(ChoiceFilter::new('requesterType', 'Profil')->setChoices(array_flip(ExternalResourceRequest::REQUESTER_TYPE_LABELS)))
            ->add(ChoiceFilter::new('requestKind', 'Type')->setChoices(array_flip(ExternalResourceRequest::REQUEST_KIND_LABELS)))
            ->add(BooleanFilter::new('privacyConsent', 'Consentement RGPD'))
            ->add(EntityFilter::new('requester', 'Compte lié'))
            ->add(TextFilter::new('organizationName', 'Organisation'))
            ->add(TextFilter::new('companySiret', 'SIRET'));
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_EDIT === $pageName) {
            yield FormField::addPanel('Traitement');
            yield TextField::new('requestNumber', 'N°')->hideOnForm();
            yield ChoiceField::new('status', 'Statut')
                ->setChoices(array_flip(ExternalResourceRequest::STATUS_LABELS))
                ->renderAsBadges(self::STATUS_BADGES)
                ->setColumns(4);
            yield DateTimeField::new('acknowledgedAt', 'Accusée le')->setColumns(4);
            yield DateTimeField::new('processedAt', 'Traitée le')->setColumns(4);

            yield FormField::addPanel('Qualification');
            yield ChoiceField::new('requesterType', 'Profil')
                ->setChoices(array_flip(ExternalResourceRequest::REQUESTER_TYPE_LABELS))
                ->setColumns(4);
            yield ChoiceField::new('requestKind', 'Type')
                ->setChoices(array_flip(ExternalResourceRequest::REQUEST_KIND_LABELS))
                ->setColumns(4);
            yield TextField::new('subject', 'Objet')->setColumns(4);
            yield TextareaField::new('message', 'Description');
            yield TextareaField::new('additionalInformation', 'Information complémentaire');

            return;
        }

        yield TextField::new('requestNumber', 'N°')->hideOnForm();
        yield ChoiceField::new('requesterType', 'Profil')
            ->setChoices(array_flip(ExternalResourceRequest::REQUESTER_TYPE_LABELS))
            ->renderAsBadges([
                ExternalResourceRequest::REQUESTER_TYPE_USAGER => 'secondary',
                ExternalResourceRequest::REQUESTER_TYPE_PROFESSIONAL => 'info',
                ExternalResourceRequest::REQUESTER_TYPE_AGENT => 'warning',
            ]);
        yield TextField::new('subject', 'Objet');
        yield ChoiceField::new('requestKind', 'Type')
            ->setChoices(array_flip(ExternalResourceRequest::REQUEST_KIND_LABELS))
            ->renderAsBadges([
                ExternalResourceRequest::REQUEST_KIND_MAP => 'success',
                ExternalResourceRequest::REQUEST_KIND_DATA => 'primary',
                ExternalResourceRequest::REQUEST_KIND_MIXED => 'warning',
            ]);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(array_flip(ExternalResourceRequest::STATUS_LABELS))
            ->renderAsBadges(self::STATUS_BADGES);
        yield TextField::new('requesterDisplayName', 'Demandeur')->onlyOnIndex();
        yield EmailField::new('email', 'Email')->hideOnIndex();
        yield TextField::new('organizationName', 'Organisation')->hideOnIndex();
        yield TextField::new('companySiret', 'SIRET')->hideOnIndex();
        yield TextField::new('postalCode', 'Code postal')->hideOnIndex();
        yield TextField::new('city', 'Ville')->hideOnIndex();
        yield TextField::new('phoneNumber', 'Téléphone')->hideOnIndex();
        yield AssociationField::new('requester', 'Compte lié')->hideOnIndex();
        yield TextareaField::new('message', 'Description')->hideOnIndex();
        yield TextareaField::new('additionalInformation', 'Information complémentaire')->hideOnIndex();
        yield ArrayField::new('networkTypes', 'Réseaux')->hideOnIndex();
        yield ArrayField::new('dataFormats', 'Formats données')->hideOnIndex();
        yield ArrayField::new('mapFormats', 'Formats carte')->hideOnIndex();
        yield TextField::new('projectionSystem', 'Projection')->hideOnIndex();
        yield TextField::new('mapScale', 'Échelle')->hideOnIndex();
        yield TextField::new('deliveryDestination', 'Destination')->hideOnIndex();
        yield BooleanField::new('privacyConsent', 'Consentement RGPD')->hideOnIndex();
        yield TextField::new('noticeVersion', 'Version mention')->hideOnIndex();
        yield DateTimeField::new('submittedAt', 'Date soumission');
        yield DateTimeField::new('acknowledgedAt', 'Accusée le')->hideOnIndex();
        yield DateTimeField::new('processedAt', 'Traitée le')->hideOnIndex();

        if (Crud::PAGE_DETAIL === $pageName) {
            yield FormField::addPanel('Demandeur');
            yield TextField::new('requesterDisplayName', 'Nom complet');
            yield EmailField::new('email', 'Email');
            yield TextField::new('phoneNumber', 'Téléphone');
            yield TextField::new('organizationName', 'Organisation');
            yield TextField::new('companySiret', 'SIRET');
            yield TextField::new('postalCode', 'Code postal');
            yield TextField::new('city', 'Ville');
            yield AssociationField::new('requester', 'Compte lié');

            yield FormField::addPanel('Spécifications');
            yield ArrayField::new('networkTypes', 'Réseaux');
            yield ArrayField::new('dataFormats', 'Formats données');
            yield TextField::new('projectionSystem', 'Projection');
            yield ArrayField::new('mapFormats', 'Formats carte');
            yield TextField::new('mapScale', 'Échelle');
            yield TextField::new('deliveryDestination', 'Destination');

            yield FormField::addPanel('Conformité');
            yield BooleanField::new('privacyConsent', 'Consentement RGPD');
            yield TextField::new('noticeVersion', 'Version mention');
            yield DateTimeField::new('createdAt', 'Créée le');
            yield DateTimeField::new('updatedAt', 'Mise à jour le');
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof ExternalResourceRequest) {
            $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);
            $previousStatus = (string) ($originalData['status'] ?? '');

            if ($entityInstance->getStatus() === ExternalResourceRequest::STATUS_ACKNOWLEDGED && $entityInstance->getAcknowledgedAt() === null) {
                $entityInstance->setAcknowledgedAt(new \DateTimeImmutable());
            }

            if (\in_array($entityInstance->getStatus(), [
                ExternalResourceRequest::STATUS_PROCESSED,
                ExternalResourceRequest::STATUS_REJECTED,
                ExternalResourceRequest::STATUS_ARCHIVED,
            ], true) && $entityInstance->getProcessedAt() === null) {
                $entityInstance->setProcessedAt(new \DateTimeImmutable());
            }

            parent::updateEntity($entityManager, $entityInstance);

            if ($previousStatus !== '' && $previousStatus !== $entityInstance->getStatus()) {
                $this->requestNotificationService->sendExternalRequestStatusUpdated($entityInstance);
            }

            return;
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
