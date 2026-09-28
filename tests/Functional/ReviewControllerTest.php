<?php

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ReviewControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private KernelBrowser $client;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testAuthenticateUserCanSubmitReview(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct();
        $this->em->flush();

        $this->client->loginUser($user);
        $this->client->request('POST', '/product/' . $product->getSlug() . '/review', [
            'rating' => 5,
            'comment' => 'Très bon produit, avis de test.',
        ]);

        $this->assertResponseRedirects('/product/' . $product->getSlug());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'Merci, ton avis a été soumis');
    }

    public function testCannotSubmitTwoReviewsForSameProduct(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct();
        $this->em->flush();

        $this->client->loginUser($user);
        $this->client->request('POST', '/product/' . $product->getSlug() . '/review', [
            'rating' => 4,
            'comment' => 'Premier avis.',
        ]);
        $this->client->request('POST', '/product/' . $product->getSlug() . '/review', [
            'rating' => 3,
            'comment' => 'Deuxième avis, ne devrait pas passer.',
        ]);

        $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'déjà laissé un avis');
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('reviewer-' . uniqid() . '@test.local');
        $user->setFirstname('Reviewer');
        $user->setLastname('Test');
        $user->setIsVerified(true);
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $this->em->persist($user);

        return $user;
    }

    private function createProduct(): Product
    {
        $category = new Category();
        $category->setName('Cat Avis ' . uniqid());
        $category->setSlug('cat-avis-' . uniqid());
        $this->em->persist($category);

        $product = new Product();
        $product->setName('Produit Avis Test');
        $product->setSlug('produit-avis-test-' . uniqid());
        $product->setCategory($category);
        $this->em->persist($product);

        return $product;
    }
}
