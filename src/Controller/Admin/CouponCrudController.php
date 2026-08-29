<?php

namespace App\Controller\Admin;

use App\Entity\Coupon;
use App\Enum\CouponType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Coupon>
 */
class CouponCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coupon::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Coupons')
            ->setEntityLabelInSingular('Coupon')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('code', 'Code'),
            ChoiceField::new('type', 'Type')
                ->setChoices(array_combine(
                    array_map(fn (CouponType $t) => $t->name, CouponType::cases()),
                    CouponType::cases()
                )),
            TextField::new('value', 'Valeur'),
            TextField::new('minAmount', 'Montant minimum')->hideOnIndex(),
            IntegerField::new('usageLimit', 'Limite d\'utilisation')->setFormTypeOption('disabled', true),
            DateTimeField::new('expiresAt', 'Expire le')->hideOnIndex(),
            BooleanField::new('isActive', 'Actif'),
        ];
    }
}
