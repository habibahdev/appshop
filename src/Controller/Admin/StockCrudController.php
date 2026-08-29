<?php

namespace App\Controller\Admin;

use App\Entity\Stock;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

/**
 * @extends AbstractCrudController<Stock>
 */
class StockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Stock::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Stocks')
            ->setEntityLabelInSingular('Stock')
            ->setDefaultSort(['updatedAt' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('variant', 'Variante')->setFormTypeOption('disabled', true),
            IntegerField::new('qty', 'Qté')->setFormTypeOption('disabled', true),
            IntegerField::new('alertThreshold', 'Seuil d\'alerte'),
            AssociationField::new('stockMovements', 'Mouvements')->onlyOnDetail(),
            DateTimeField::new('updatedAt', 'Mis à jour le')->setFormTypeOption('disabled', true)
        ];
    }
}
