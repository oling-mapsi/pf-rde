<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Access\Entity\ExternalResourceRequest;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ExternalResourceRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ExternalResourceRequest::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('requester', 'Demandeur');
        yield TextField::new('subject', 'Objet');
        yield TextareaField::new('message', 'Message')->hideOnIndex();
        yield TextField::new('status', 'Statut');
        yield DateTimeField::new('submittedAt', 'Date soumission')->hideOnIndex();
    }
}

