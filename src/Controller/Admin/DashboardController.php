<?php

namespace App\Controller\Admin;

use App\Enum\PurchaseStatus;
use App\Repository\PurchaseRepository;
use App\Repository\StockRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private PurchaseRepository $purchaseRepository,
        private StockRepository $stockeRepository
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'revenueThisMonth' => $this->purchaseRepository
                ->getRevenueSince(new \DateTimeImmutable('first day of this month')),
            'purchasesThisMonth' => $this->purchaseRepository
                ->countSince(new \DateTimeImmutable('first day of this month')),
            'pendingPurchases' => $this->purchaseRepository->count(['status' => PurchaseStatus::PAID]),
            'lowStockEntries' => $this->stockeRepository->findLowStock()
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('App Shop')
            ->renderContentMaximized()
        ;
    }

    public function configureMenuItems(): iterable
    {
        $pendingCount = $this->purchaseRepository->count(['status' => PurchaseStatus::PAID]);

        yield MenuItem::linkToDashboard('Tableau de board', 'fa fa-home');
        yield MenuItem::section('Catalogue');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fa fa-folder');
        yield MenuItem::linkTo(ProductCrudController::class, 'Produits', 'fa fa-box');
        yield MenuItem::linkTo(ProductVariantCrudController::class, 'Variantes', 'fa fa-layer-group');

        yield MenuItem::section('Ventes');
        yield MenuItem::linkTo(PurchaseCrudController::class, 'Commandes', 'fa fa-receipt')
            ->setBadge($pendingCount > 0 ? $pendingCount : null, 'success');
        yield MenuItem::linkTo(CouponCrudController::class, 'Coupons', 'fa fa-tag');

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkTo(UserCrudController::class, 'Clients', 'fa fa-user');

        yield MenuItem::section('Modération');
        yield MenuItem::linkTo(ReviewCrudController::class, 'Avis', 'fa fa-star');
    }
}
