<?php

declare(strict_types=1);

namespace App\UI\Controller\Admin;

use App\Domain\Access\Entity\User;
use App\Domain\Content\Entity\ContactMessage;
use App\Domain\Content\Entity\News;
use App\Domain\Content\Entity\Page;
use App\Domain\Cartography\Entity\StaticMap;
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
        yield MenuItem::linkToCrud('Pages', 'fa fa-file-lines', Page::class);
        yield MenuItem::linkToCrud('Actualites', 'fa fa-newspaper', News::class);
        yield MenuItem::linkToCrud('Cartes statiques', 'fa fa-map', StaticMap::class);
        yield MenuItem::linkToCrud('Messages contact', 'fa fa-envelope', ContactMessage::class);
        yield MenuItem::section('Acces');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-home', 'app_home');
    }
}
