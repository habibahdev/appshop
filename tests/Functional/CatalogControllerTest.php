<?php

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CatalogControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    #[Override]
    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCatalogPageDisplayActiveProducts(): void
    {
        $this->createProduct('T-shirt Test Fonctionnel', '25.00');
        $this->client->request('GET', '/catalog');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'T-shirt Test Fonctionnel');
    }

    public function testInactiveProductIsNotDisplay(): void
    {
        $this->createProduct('Produit Caché Test', '10.00', 10, false);
        $this->client->request('GET', '/catalog');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('body', 'Produit Caché Test');
    }

    public function testProductShowPageDisplaysDetails()
    {
        $product = $this->createProduct('Casquette Test', '15.00');
        $this->client->request('GET', '/product/' . $product->getSlug());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Casquette Test');
    }

    public function testUnknownProductSlugReturns404(): void
    {
        $this->client->request('GET', '/product/unknow-product');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCatalogPaginationSecondPageIsAccessible(): void
    {
        for ($i = 0; $i < 13; $i++) {
            $this->createProduct('Produit Pagination ' . $i, '9.90');
        }
        $this->client->request('GET', '/catalog', ['page' => 2]);
        $this->assertResponseIsSuccessful();
    }

    private function createProduct(string $name, string $price, int $stockQty = 10, bool $isActive = true): Product
    {
        $category = new Category();
        $category->setName('Catégorie ' . uniqid());
        $category->setSlug('cat-' . uniqid());
        $this->em->persist($category);

        $product = new Product();
        $product->setName($name);
        $product->setSlug(strtolower(str_replace(' ', '-', $name)) . '-' . uniqid());
        $product->setCategory($category);
        $product->setIsActive($isActive);
        $this->em->persist($product);

        $variant = new ProductVariant();
        $variant->setProduct($product);
        $variant->setSku('SKU-' . uniqid());
        $variant->setPrice($price);
        $this->em->persist($variant);

        $stock = new Stock();
        $stock->setVariant($variant);
        $stock->setQty($stockQty);
        $this->em->persist($stock);
        $this->em->flush();

        return $product;
    }
}
