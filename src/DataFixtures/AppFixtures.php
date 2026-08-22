<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Stock;
use App\Entity\Coupon;
use App\Entity\Review;
use App\Entity\Product;
use App\Entity\Category;
use App\Enum\CouponType;
use App\Enum\ReviewStatus;
use App\Entity\ProductVariant;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValue;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->loadUsers($manager);
        $categories = $this->loadCategories($manager);
        $attributes = $this->loadAttributes($manager);
        $products = $this->loadProducts($manager, $categories, $attributes);
        $this->loadCoupons($manager);
        $this->loadReviews($manager, $products, $users);

        $manager->flush();
    }

    /**
     * @return array<string, User>
     */
    private function loadUsers(ObjectManager $manager): array
    {
        $admin = new User();
        $admin->setEmail('admin@appshop.test');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        $customer = new User();
        $customer->setEmail('client@appshop.test');
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
        $names = ['T-shirts', 'Vestes', 'Accessoires'];
        $categories = [];

        foreach ($names as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug($this->slugify($name));
            $manager->persist($category);
            $categories[$name] = $category;
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
        foreach (['Noir', 'Blanc', 'Bleu'] as $v) {
            $value = new ProductAttributeValue();
            $value->setAttribute($couleur);
            $value->setValue($v);
            $manager->persist($value);
            $values['couleur_' . $v] = $value;
        }

        foreach (['S', 'M', 'L', 'XL'] as $v) {
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
     * @return array<int, Product>
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
                'name' => 'Veste Légère',
                'category' => 'Vestes',
                'price' => '89.00',
                'description' => 'Coupe-vent déperlant.'
            ],
            [
                'name' => 'Casquette Brodée',
                'category' => 'Accessoires',
                'price' => '19.90',
                'description' => 'Broderie fine, ajustable.'
            ],
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
                'category' => 'T-shirts',
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
                'category' => 'Vestes',
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
                'category' => 'Vestes',
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
                'name' => 'Parka Imperméable',
                'category' => 'Vestes',
                'price' => '129.00',
                'description' => 'Capuche amovible, tissu déperlant.'
            ],
            [
                'name' => 'Veste Sans Manches',
                'category' => 'Vestes',
                'price' => '69.00',
                'description' => 'Doudoune sans manches, très légère.'
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
                'name' => 'Sac Bandoulière',
                'category' => 'Accessoires',
                'price' => '49.90',
                'description' => 'Format compact, plusieurs poches.'
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

        foreach ($catalogue as $i => $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setSlug($this->slugify($data['name']));
            $product->setDescription($data['description']);
            $product->setCategory($categories[$data['category']]);
            $manager->persist($product);

            $colors = ['Noir', 'Blanc', 'Bleu'];
            $sizes = ['S', 'M', 'L', 'XL'];

            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    $variant = new ProductVariant();
                    $variant->setProduct($product);
                    $variant->setSku(sprintf('SKU-%d-%s-%s', $i, strtoupper($color), $size));
                    $variant->setPrice($data['price']);
                    $variant->addAttributeValue($attributeValues['couleur_' . $color]);
                    $variant->addAttributeValue($attributeValues['taille_' . $size]);
                    $manager->persist($variant);

                    // Stock créé directement ici (pas via StockService) : c'est un seed initial,
                    // pas un mouvement à tracer dans l'historique.
                    $stock = new Stock();
                    $stock->setVariant($variant);
                    $stock->setQty(random_int(0, 30));
                    $stock->setAlertThreshold(5);
                    $manager->persist($stock);
                }
            }

            $products[] = $product;
        }

        return $products;
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
     */
    private function loadReviews(ObjectManager $manager, array $products, array $users): void
    {
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

    private function slugify(string $text): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));

        return $slug . '-' . substr(md5(uniqid('', true)), 0, 6);
    }
}
