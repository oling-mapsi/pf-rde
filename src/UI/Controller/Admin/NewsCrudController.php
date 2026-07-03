<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Content\Entity\News;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class NewsCrudController extends AbstractCrudController
{
    public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    public static function getEntityFqcn(): string
    {
        return News::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile(Asset::new('/vendor/tinymce/tinymce.min.js')->onlyOnForms())
            ->addCssFile(Asset::new('/admin-assets/page-rich-text.css')->onlyOnForms())
            ->addJsFile(Asset::new('/admin-assets/page-rich-text.js')->onlyOnForms()->defer());
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextareaField::new('summary')->hideOnIndex();
        yield TextareaField::new('body')
            ->hideOnIndex()
            ->setNumOfRows(20)
            ->setHelp('Éditeur avancé PF : mise en page riche, tableaux, images et blocs de contenu.')
            ->setFormTypeOption('attr.data-rich-text-upload-url', $this->generateUrl('admin_page_content_image_upload'))
            ->setFormTypeOption('attr.data-rich-text-upload-token', (string) $this->csrfTokenManager->getToken('page_content_image_upload'))
            ->setFormTypeOption('attr.data-admin-rich-text', 'site');
        yield TextField::new('coverImagePath')->hideOnIndex();
        yield TextField::new('status');
        yield DateTimeField::new('publishedAt')->hideOnIndex();
    }
}
