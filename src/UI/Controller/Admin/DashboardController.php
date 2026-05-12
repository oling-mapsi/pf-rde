<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin_dashboard')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Routes de Guadeloupe - Portail SIG');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-chart-line');
        yield MenuItem::section('Contenus');
        yield MenuItem::linkToRoute('Builder page d’accueil', 'fa fa-layer-group', 'admin_homepage_builder');
        yield MenuItem::linkTo(HomepageContentCrudController::class, 'Héros d’accueil', 'fa fa-heading');
        yield MenuItem::linkTo(HomepageSectionCrudController::class, 'Sections d’accueil', 'fa fa-grip');
        yield MenuItem::linkTo(PageCrudController::class, 'Pages', 'fa fa-file-lines');
        yield MenuItem::linkTo(NewsCrudController::class, 'Actualités', 'fa fa-newspaper');
        yield MenuItem::linkTo(DataSourceCrudController::class, 'Sources de données', 'fa fa-database');
        yield MenuItem::linkTo(DataCategoryCrudController::class, 'Catégories de ressources', 'fa fa-folder-tree');
        yield MenuItem::linkTo(MapThemeCrudController::class, 'Thèmes cartothèque', 'fa fa-tags');
        yield MenuItem::linkTo(ContactMessageCrudController::class, 'Messages contact', 'fa fa-envelope');
        yield MenuItem::section('Demandes');
        yield MenuItem::linkTo(AgentRequestCrudController::class, 'Demandes de cartes', 'fa fa-clipboard-list');
        yield MenuItem::linkTo(ExternalResourceRequestCrudController::class, 'Demandes externes', 'fa fa-inbox');
        yield MenuItem::section('Accès');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-home', 'app_home');
    }
}
