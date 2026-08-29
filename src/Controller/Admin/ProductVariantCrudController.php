<?php

namespace App\Controller\Admin;

use App\Entity\ProductVariant;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<ProductVariant>
 */
class ProductVariantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductVariant::class;
    }

    public function configreCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Variantes')
            ->setEntityLabelInSingular('Variante')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sku', 'SKU'),
            AssociationField::new('product', 'Produit associé'),
            MoneyField::new('price', 'Prix')->setCurrency('EUR')->setStoredAsCents(false),
            AssociationField::new('attributeValues', 'Attributs (couleur, taile...)'),
            BooleanField::new('isActive', 'Active'),
        ];
    }
}
