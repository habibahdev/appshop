<?php

namespace App\Controller\Admin;

use App\Entity\Purchase;
use App\Enum\PurchaseStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Purchase>
 */
class PurchaseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Purchase::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Commandes')
            ->setEntityLabelInSingular('Commande')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('reference', 'Référence')->setFormTypeOption('disabled', true),
            AssociationField::new('user', 'Client')->setFormTypeOption('disabled', true),
            ChoiceField::new('status', 'Statut')
                ->setChoices(array_combine(
                    array_map(
                        fn(PurchaseStatus $s) => $s->label(),
                        PurchaseStatus::cases()
                    ),
                    PurchaseStatus::cases()
                ))->setFormTypeOption('disabled', true),
            TextField::new('total', 'Total')->setFormTypeOption('disabled', true),
            TextField::new('discount', 'Réduction')->hideOnIndex(),
            AssociationField::new('coupon', 'Coupon utilisé')->hideOnIndex(),
            TextareaField::new('delivery', 'Adresse de livraison')->hideOnIndex(),
            TextField::new('stripe', 'Stripe ID')->hideOnIndex()->setFormTypeOption('disabled', true),
            AssociationField::new('details', 'Détails')->onlyOnDetail(),
            AssociationField::new('invoice', 'Facture')->onlyOnDetail(),
            DateTimeField::new('createdAt', 'Créée le')->setFormTypeOption('disabled', true),
        ];
    }
}
