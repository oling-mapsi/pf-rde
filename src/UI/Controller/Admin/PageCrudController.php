<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Content\Entity\Page;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class PageCrudController extends AbstractCrudController
{
    public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile(Asset::new('/admin/page-rich-text.css')->onlyOnForms())
            ->addJsFile(Asset::new('/admin/page-rich-text.js')->onlyOnForms()->defer());
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextareaField::new('summary')->hideOnIndex();
        yield TextEditorField::new('content')
            ->hideOnIndex()
            ->setNumOfRows(18)
            ->setHelp('Glissez-déposez une image dans l’éditeur ou collez-la directement depuis le presse-papiers.')
            ->setTrixEditorConfig([
                'blockAttributes' => [
                    'heading1' => ['tagName' => 'h2'],
                ],
            ])
            ->setFormTypeOption('attr.data-rich-text-upload-url', $this->generateUrl('admin_page_content_image_upload'))
            ->setFormTypeOption('attr.data-rich-text-upload-token', (string) $this->csrfTokenManager->getToken('page_content_image_upload'))
            ->setFormTypeOption('attr.data-rich-text-theme', 'site');
        yield TextField::new('status');
        yield TextField::new('legalType')->hideOnIndex();
        yield BooleanField::new('systemPage');
        yield DateTimeField::new('publishedAt')->hideOnIndex();
    }
}
