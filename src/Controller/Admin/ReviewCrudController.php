<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Enum\ReviewStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

/**
 * @extends AbstractCrudController<Review>
 */
class ReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Avis')
            ->setEntityLabelInSingular('Avis')
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
            AssociationField::new('product', 'Produit')->setFormTypeOption('disabled', true),
            AssociationField::new('user', 'Client')->setFormTypeOption('disabled', true),
            IntegerField::new('rating', 'Note')->setFormTypeOption('disabled', true),
            TextareaField::new('comment', 'Commentaire')->setFormTypeOption('disabled', true),
            ChoiceField::new('status', 'Statut')
                ->setChoices(array_combine(
                    array_map(
                        fn (ReviewStatus $s) => $s->label(),
                        ReviewStatus::cases()
                    ),
                    ReviewStatus::cases()
                )),
        ];
    }
}
