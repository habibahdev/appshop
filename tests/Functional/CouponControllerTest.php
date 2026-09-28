<?php

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Coupon;
use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\Stock;
use App\Enum\CouponType;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CouponControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testApplyValidPercentageCoupon(): void
    {
        $this->addProductToCart($this->client, '100.00');
        $coupon = new Coupon();
        $coupon->setCode('TESTPROMO');
        $coupon->setType(CouponType::PERCENTAGE);
        $coupon->setValue('10');
        $this->em->persist($coupon);
        $this->em->flush();

        $this->client->request('POST', '/cart/coupon', ['code' => 'testpromo']);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('body', 'TESTPROMO');
        $this->assertSelectorTextContains('body', '10,00');
    }

    public function testApplyUnknownCouponShowsError(): void
    {
        $this->addProductToCart($this->client, '50.00');
        $this->client->request('POST', '/cart/coupon', ['code' => 'INCONNU']);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'invalide');
    }

    public function testCouponBelowMinAmountIsRejected(): void
    {
        $this->addProductToCart($this->client, '20.00');
        $coupon = new Coupon();
        $coupon->setCode('MINAMOUNT');
        $coupon->setType(CouponType::FIXED);
        $coupon->setValue('5.00');
        $coupon->setMinAmount('50.00');
        $this->em->persist($coupon);
        $this->em->flush();

        $this->client->request('POST', '/cart/coupon', ['code' => 'MINAMOUNT']);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('body', 'minimum');
    }

    private function addProductToCart(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $price): void
    {
        $category = new Category();
        $category->setName('Cat Coupon ' . uniqid());
        $category->setSlug('cat-coupon-' . uniqid());
        $this->em->persist($category);

        $product = new Product();
        $product->setName('Produit Coupon Test');
        $product->setSlug('produit-coupon-test-' . uniqid());
        $product->setCategory($category);
        $this->em->persist($product);

        $variant = new ProductVariant();
        $variant->setProduct($product);
        $variant->setSku('SKU-' . uniqid());
        $variant->setPrice($price);
        $this->em->persist($variant);

        $stock = new Stock();
        $stock->setVariant($variant);
        $stock->setQty(10);
        $this->em->persist($stock);
        $this->em->flush();

        $client->request('POST', '/cart/add', ['variant' => $variant->getId(), 'qty' => 1]);
    }
}
