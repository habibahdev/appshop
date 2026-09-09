<?php

namespace App\DataFixtures;

use App\Util\Money;
use App\Entity\User;
use App\Entity\Stock;
use App\Entity\Coupon;
use App\Entity\Detail;
use App\Entity\Review;
use App\Entity\Address;
use App\Entity\Invoice;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Purchase;
use App\Enum\CouponType;
use App\Enum\ReviewStatus;
use App\Enum\PurchaseStatus;
use App\Service\StockService;
use App\Entity\ProductVariant;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValue;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private StockService $stockService
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->loadUsers($manager);
        $this->loadAddresses($manager, $users);
        $categories = $this->loadCategories($manager);
        $attributes = $this->loadAttributes($manager);
        [$products, $variantsByProduct] = $this->loadProducts($manager, $categories, $attributes);
        $this->loadCoupons($manager);

        $manager->flush();

        $purchasedProductIds = $this->loadPurchases($manager, $users, $variantsByProduct);
        $this->loadReviews($manager, $products, $users, $purchasedProductIds);
        $manager->flush();
    }

    /**
     * @return array<string, User>
     */
    private function loadUsers(ObjectManager $manager): array
    {
        $admin = new User();
        $admin->setEmail('admin@appshop.test');
        $admin->setFirstname('Admin');
        $admin->setLastname('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPhone('+33612345678');
        $admin->setPassword($this->hasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        $customer = new User();
        $customer->setEmail('client@appshop.test');
        $customer->setFirstname('Jean');
        $customer->setLastname('Jacques');
        $customer->setPhone('+33698765432');
        $customer->setRoles(['ROLE_USER']);
        $customer->setPassword($this->hasher->hashPassword($customer, 'password'));
        $manager->persist($customer);

        return ['admin' => $admin, 'customer' => $customer];
    }

    /**
     * @return array<string, Category>
     */
    private function loadCategories(ObjectManager $manager): array
    {
        $names = ['T-shirts', 'Vestes', 'Manteaux', 'Jeans', 'Pantalons', 'Chaussures', 'Sacs', 'Accessoires'];
        $categories = [];

        foreach ($names as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug($this->slugify($name));
            $manager->persist($category);
            $categories[$name] = $category;
        }

        $subCategories = [
            'T-shirts' => ['Manches courtes', 'Manches longues', 'Sans manches'],
            'Vestes' => ['Légéres', 'Matelassées'],
            'Chaussures' => ['Sneakers', 'Bottines']
        ];

        foreach ($subCategories as $parentName => $children) {
            foreach ($children as $childName) {
                $child = new Category();
                $child->setName($childName);
                $child->setSlug($this->slugify($parentName . '-' . $childName));
                $child->setParent($categories[$parentName]);
                $manager->persist($child);
                $categories[$parentName . ' > ' . $childName] = $child;
            }
        }

        return $categories;
    }

    /**
     * @return array<string, ProductAttributeValue>
     */
    private function loadAttributes(ObjectManager $manager): array
    {
        $couleur = new ProductAttribute();
        $couleur->setName('Couleur');
        $manager->persist($couleur);

        $taille = new ProductAttribute();
        $taille->setName('Taille');
        $manager->persist($taille);

        $values = [];
        foreach (['Noir', 'Blanc', 'Bleu', 'Orange'] as $v) {
            $value = new ProductAttributeValue();
            $value->setAttribute($couleur);
            $value->setValue($v);
            $manager->persist($value);
            $values['couleur_' . $v] = $value;
        }

        foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $v) {
            $value = new ProductAttributeValue();
            $value->setAttribute($taille);
            $value->setValue($v);
            $manager->persist($value);
            $values['taille_' . $v] = $value;
        }

        return $values;
    }

    /**
     * @param array<string, Category> $categories
     * @param array<string, ProductAttributeValue> $attributeValues
     * @return array{0: array<int, Product>, 1: array<int, ProductVariant[]>}
     */
    private function loadProducts(ObjectManager $manager, array $categories, array $attributeValues): array
    {
        $catalogue = [
            [
                'name' => 'T-shirt Essentiel',
                'category' => 'T-shirts',
                'price' => '24.90',
                'description' => 'Coton bio, coupe droite.'
            ],
            [
                'name' => 'T-shirt Col Rond',
                'category' => 'T-shirts',
                'price' => '22.90',
                'description' => 'Basique intemporel.'
            ],
            [
                'name' => 'T-shirt Rayé',
                'category' => 'T-shirts',
                'price' => '26.90',
                'description' => 'Rayures marinière, coton épais.'
            ],
            [
                'name' => 'T-shirt Oversize',
                'category' => 'T-shirts',
                'price' => '28.90',
                'description' => 'Coupe ample, tissu lourd.'
            ],
            [
                'name' => 'T-shirt Manches Longues',
                'category' => 'T-shirts > Manches longues',
                'price' => '32.90',
                'description' => 'Parfait pour les saisons fraîches.'
            ],
            [
                'name' => 'T-shirt Col V',
                'category' => 'T-shirts',
                'price' => '23.90',
                'description' => 'Coupe ajustée, col en V.'
            ],
            [
                'name' => 'Veste Légère',
                'category' => 'Vestes > Légéres',
                'price' => '89.00',
                'description' => 'Coupe-vent déperlant.'
            ],
            [
                'name' => 'Veste en Jean',
                'category' => 'Vestes',
                'price' => '79.00',
                'description' => 'Denim brut, coupe classique.'
            ],
            [
                'name' => 'Veste Matelassée',
                'category' => 'Vestes > Matelassées',
                'price' => '99.00',
                'description' => 'Chaude et légère, doublure polaire.'
            ],
            [
                'name' => 'Blouson Bomber',
                'category' => 'Vestes',
                'price' => '95.00',
                'description' => 'Style aviateur, finitions côtelées.'
            ],
            [
                'name' => 'Veste Sans Manches',
                'category' => 'Vestes',
                'price' => '69.00',
                'description' => 'Doudoune sans manches, très légère.'
            ],
            [
                'name' => 'Manteau en Laine',
                'category' => 'Manteaux',
                'price' => '159.00',
                'description' => 'Laine mélangée, coupe droite.'
            ],
            [
                'name' => 'Trench Classique',
                'category' => 'Manteaux',
                'price' => '179.00',
                'description' => 'Coton ciré, ceinture ajustable.'
            ],
            [
                'name' => 'Parka Imperméable',
                'category' => 'Manteaux',
                'price' => '129.00',
                'description' => 'Capuche amovible, tissu déperlant.'
            ],
            [
                'name' => 'Manteau Long Oversize',
                'category' => 'Manteaux',
                'price' => '169.00',
                'description' => 'Coupe ample, tissu épais.'
            ],
            [
                'name' => 'Jean Slim',
                'category' => 'Jeans',
                'price' => '59.90',
                'description' => 'Coupe ajustée, stretch confortable.'
            ],
            [
                'name' => 'Jean Droit',
                'category' => 'Jeans',
                'price' => '54.90',
                'description' => 'Coupe intemporelle, denim brut.'
            ],
            [
                'name' => 'Jean Mom',
                'category' => 'Jeans',
                'price' => '64.90',
                'description' => 'Taille haute, coupe rétro.'
            ],
            [
                'name' => 'Jean Skinny',
                'category' => 'Jeans',
                'price' => '57.90',
                'description' => 'Coupe près du corps, très extensible.'
            ],
            [
                'name' => 'Pantalon Chino',
                'category' => 'Pantalons',
                'price' => '49.90',
                'description' => 'Coton stretch, coupe droite.'
            ],
            [
                'name' => 'Pantalon de Costume',
                'category' => 'Pantalons',
                'price' => '69.90',
                'description' => 'Coupe cintrée, tissu structuré.'
            ],
            [
                'name' => 'Jogger Cargo',
                'category' => 'Pantalons',
                'price' => '44.90',
                'description' => 'Poches multiples, taille élastique.'
            ],
            [
                'name' => 'Sneakers Basses',
                'category' => 'Chaussures',
                'price' => '79.00',
                'description' => 'Cuir et toile, semelle souple.'
            ],
            [
                'name' => 'Bottines en Cuir',
                'category' => 'Chaussures',
                'price' => '119.00',
                'description' => 'Cuir pleine fleur, semelle gomme.'
            ],
            [
                'name' => 'Mocassins',
                'category' => 'Chaussures',
                'price' => '89.00',
                'description' => 'Cuir souple, doublure cuir.'
            ],
            [
                'name' => 'Baskets Running',
                'category' => 'Chaussures',
                'price' => '95.00',
                'description' => 'Amorti renforcé, mesh respirant.'
            ],
            [
                'name' => 'Sac Bandoulière',
                'category' => 'Sacs',
                'price' => '49.90',
                'description' => 'Format compact, plusieurs poches.'
            ],
            [
                'name' => 'Sac à Dos Urbain',
                'category' => 'Sacs',
                'price' => '69.90',
                'description' => 'Compartiment ordinateur, tissu résistant.'
            ],
            [
                'name' => 'Cabas en Toile',
                'category' => 'Sacs',
                'price' => '34.90',
                'description' => 'Grand format, anses renforcées.'
            ],
            [
                'name' => 'Casquette Brodée',
                'category' => 'Accessoires',
                'price' => '19.90',
                'description' => 'Broderie fine, ajustable.'
            ],
            [
                'name' => 'Bonnet en Laine',
                'category' => 'Accessoires',
                'price' => '15.90',
                'description' => 'Laine mérinos, tricot serré.'
            ],
            [
                'name' => 'Écharpe Oversize',
                'category' => 'Accessoires',
                'price' => '24.90',
                'description' => 'Grand format, très douce.'
            ],
            [
                'name' => 'Ceinture en Cuir',
                'category' => 'Accessoires',
                'price' => '34.90',
                'description' => 'Cuir pleine fleur, boucle métal.'
            ],
            [
                'name' => 'Chaussettes (lot de 3)',
                'category' => 'Accessoires',
                'price' => '12.90',
                'description' => 'Coton peigné, tailles mixtes.'
            ],
            [
                'name' => 'Gants Tactiles',
                'category' => 'Accessoires',
                'price' => '18.90',
                'description' => 'Compatibles écrans tactiles.'
            ],
            [
                'name' => 'Portefeuille Slim',
                'category' => 'Accessoires',
                'price' => '29.90',
                'description' => 'Format carte, cuir souple.'
            ],
        ];

        $products = [];
        $variantsByProduct = [];

        foreach ($catalogue as $i => $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setSlug($this->slugify($data['name']));
            $product->setDescription($data['description']);
            $product->setCategory($categories[$data['category']]);
            $manager->persist($product);

            $colors = ['Noir', 'Blanc', 'Bleu', 'Orange'];
            $sizes = ['S', 'M', 'L', 'XL'];
            $variants = [];

            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    $variant = new ProductVariant();
                    $variant->setProduct($product);
                    $variant->setSku(sprintf('SKU-%d-%s-%s', $i, strtoupper($color), $size));
                    $variant->setPrice($data['price']);
                    $variant->addAttributeValue($attributeValues['couleur_' . $color]);
                    $variant->addAttributeValue($attributeValues['taille_' . $size]);
                    $manager->persist($variant);

                    $stock = new Stock();
                    $stock->setVariant($variant);
                    $stock->setQty(random_int(10, 30));
                    $stock->setAlertThreshold(5);
                    $manager->persist($stock);

                    $variants[] = $variant;
                }
            }

            $products[] = $product;
            $variantsByProduct[spl_object_id($product)] = $variants;
        }

        return [$products, $variantsByProduct];
    }

    /**
     * @param array<string, User> $users
     * @param array<int, ProductVariant[]> $variantsByProduct
     * @return int[] ids des Product effectivement achetés par le client (pour loadReviews)
     */
    private function loadPurchases(ObjectManager $manager, array $users, array $variantsByProduct): array
    {
        $customer = $users['customer'];
        $purchasedProductIds = [];

        $scenarios = [
            ['status' => PurchaseStatus::DELIVERED, 'withInvoice' => true],
            ['status' => PurchaseStatus::PREPARATION, 'withInvoice' => true],
            ['status' => PurchaseStatus::PENDING, 'withInvoice' => false],
            ['status' => PurchaseStatus::PREPARATION, 'withInvoice' => true],
            ['status' => PurchaseStatus::PENDING, 'withInvoice' => false],
        ];

        $allVariants = array_merge(...array_values($variantsByProduct));

        foreach ($scenarios as $i => $scenario) {
            $purchase = new Purchase();
            $purchase->setUser($customer);
            $purchase->setReference($this->generateReference($i));
            $purchase->setDelivery("Jean Jacques\n12 rue des Lilas\n75011 Paris\nFrance");
            $purchase->setStatus($scenario['status']);

            $lineCount = random_int(1, 3);
            $chosen = (array) array_rand($allVariants, min($lineCount, count($allVariants)));

            $total = '0.00';

            foreach ($chosen as $index) {
                $variant = $allVariants[$index];
                $qty = random_int(1, 2);

                $detail = new Detail();
                $detail->setVariant($variant);
                $detail->setProductName($variant->getProduct()->getName());
                $detail->setVariantLabel($variant->getLabel());
                $detail->setProductPrice($variant->getPrice());
                $detail->setQty($qty);
                $purchase->addDetail($detail);

                $price = Money::assertNumericString(
                    $variant->getPrice(),
                    'prix de la variante'
                );


                $total = bcadd($total, bcmul($price, (string) $qty, 2), 2);
                $purchasedProductIds[] = $variant->getProduct()->getId();
            }

            $purchase->setTotal($total);
            $purchase->setDiscount('0.00');
            $manager->persist($purchase);
            $manager->flush();

            if ($scenario['status'] !== PurchaseStatus::PENDING) {
                foreach ($purchase->getDetails() as $detail) {
                    $stock = $this->stockService->getStockForVariant($detail->getVariant());
                    if ($stock && $stock->getQty() >= $detail->getQty()) {
                        $this->stockService->reserveForSale($stock, $detail->getQty(), $purchase);
                    }
                }
            }

            if ($scenario['withInvoice']) {
                $invoice = new Invoice();
                $invoice->setNumber('FAC-2026-' . str_pad((string) $purchase->getId(), 6, '0', STR_PAD_LEFT));
                $invoice->setFilename($invoice->getNumber() . '.pdf');
                $purchase->setInvoice($invoice);
                $manager->persist($invoice);
            }
        }

        return array_unique($purchasedProductIds);
    }

    private function loadCoupons(ObjectManager $manager): void
    {
        $percent = new Coupon();
        $percent->setCode('BIENVENUE10');
        $percent->setType(CouponType::PERCENTAGE);
        $percent->setValue('10');
        $percent->setMinAmount('30.00');
        $percent->setUsageLimit(100);
        $manager->persist($percent);

        $fixed = new Coupon();
        $fixed->setCode('MOINS5');
        $fixed->setType(CouponType::FIXED);
        $fixed->setValue('5.00');
        $manager->persist($fixed);
    }

    /**
     * @param array<int, Product> $products
     * @param array<string, User> $users
     * @param int[] $purchasedProductIds
     */
    private function loadReviews(
        ObjectManager $manager,
        array $products,
        array $users,
        array $purchasedProductIds
    ): void {
        $comments = [
            'Très satisfait, coupe parfaite.',
            'Bonne qualité pour le prix.',
            'Livraison rapide, produit conforme.',
        ];

        foreach (array_slice($products, 0, 2) as $product) {
            $review = new Review();
            $review->setProduct($product);
            $review->setUser($users['customer']);
            $review->setRating(random_int(3, 5));
            $review->setComment($comments[array_rand($comments)]);
            $review->setStatus(ReviewStatus::APPROVED);
            $review->setVerifiedPurchase(true);
            $manager->persist($review);
        }
    }

    /**
     * @param array<string, User> $users
     */
    private function loadAddresses(ObjectManager $manager, array $users): void
    {
        $customer = $users['customer'];

        $home = new Address();
        $home->setUser($customer);
        $home->setLabel('Maison');
        $home->setFullName('Jean Jacques');
        $home->setStreet('12 rue des Lilas');
        $home->setPostalCode('75011');
        $home->setCity('Paris');
        $home->setCountry('France');
        $home->setIsDefault(true);
        $manager->persist($home);

        $work = new Address();
        $work->setUser($customer);
        $work->setLabel('Travail');
        $work->setFullName('Jean Jacques');
        $work->setStreet('8 avenue Victor Hugo');
        $work->setPostalCode('69011');
        $work->setCity('Lyon');
        $work->setCountry('France');
        $work->setIsDefault(false);
        $manager->persist($work);
    }

    private function slugify(string $text): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));

        return $slug . '-' . substr(md5(uniqid('', true)), 0, 6);
    }

    private function generateReference(int $index): string
    {
        return 'CMD-2026-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);
    }
}
