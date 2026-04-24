<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Cartography\Entity\StaticMap;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class StaticMapCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StaticMap::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextField::new('theme')->hideOnIndex();
        yield TextareaField::new('summary')->hideOnIndex();
        yield TextareaField::new('description')->hideOnIndex();
        yield DateField::new('documentDate')->hideOnIndex();
        yield TextField::new('thumbnailPath')->hideOnIndex();
        yield TextField::new('status');
        yield DateTimeField::new('publishedAt')->hideOnIndex();
    }
}
