<?php

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartControllerTest extends WebTestCase
{
    public function testAddToCartRedirectsAndIncreasesQty(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $variant = $this->createVariant($em, '30.00', 5);

        $client->request('POST', '/cart/add', [
            'variant' => $variant->getId(),
            'qty' => 2
        ]);

        $this->assertResponseRedirects('/cart');
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Produit Panier Test');
        $this->assertSelectorTextContains('body', '60,00');
    }

    public function testAddToCartCannotExceedStock(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $variant = $this->createVariant($em, '10.00', 2);
        $client->request('POST', '/cart/add', [
            'variant' => $variant->getId(),
            'qty' => 10
        ]);
        $this->assertResponseRedirects('/cart');
        $client->followRedirect();
        $this->assertSelectorTextContains('body', '20,00');
        $this->assertSelectorTextNotContains('body', '100,00');
    }

    public function testUpdateQuantityIsCappedByStock(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $variant = $this->createVariant($em, '10.00', 4);
        $client->request('POST', '/cart/add', [
            'variant' => $variant->getId(),
            'qty' => 1
        ]);
        $client->request('POST', '/cart/update/' . $variant->getId(), ['qty' => 100]);
        $client->followRedirect();
        $this->assertSelectorTextContains('body', '40,00');
    }

    public function testRemoveFromCartEmptiesIt(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $variant = $this->createVariant($em, '10.00', 5);
        $client->request('POST', '/cart/add', [
            'variant' => $variant->getId(),
            'qty' => 1
        ]);
        $client->request('POST', '/cart/delete/' . $variant->getId());
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Ton panier est vide');
    }

    public function testCartAddWithInvalidVariantShowsErrorFlash(): void
    {
        $client = static::createClient();
        $client->request('POST', '/cart/add', [
            'variant' => 999999,
            'qty' => 1
        ]);
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'introuvable');
    }

    private function createVariant(EntityManagerInterface $em, string $price, int $stockQty): ProductVariant
    {
        $category = new Category();
        $category->setName('Cat ' . uniqid());
        $category->setSlug('cat-' . uniqid());
        $em->persist($category);

        $product = new Product();
        $product->setName('Produit Panier Test');
        $product->setSlug('produit-panier-test-' . uniqid());
        $product->setCategory($category);
        $em->persist($product);

        $variant = new ProductVariant();
        $variant->setProduct($product);
        $variant->setSku('SKU-' . uniqid());
        $variant->setPrice($price);
        $em->persist($variant);

        $stock = new Stock();
        $stock->setVariant($variant);
        $stock->setQty($stockQty);
        $em->persist($stock);
        $em->flush();

        return $variant;
    }
}
