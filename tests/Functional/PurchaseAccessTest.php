<?php

namespace App\Tests\Functional;

use App\Entity\Purchase;
use App\Entity\User;
use App\Enum\PurchaseStatus;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PurchaseAccessTest extends WebTestCase
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

    public function testOwnerCanViewtheirOwnPurchase(): void
    {
        $owner = $this->createUser('owner-' . uniqid() . '@test.local');
        $purchase = $this->createPurchase($owner);
        $this->em->flush();

        $this->client->loginUser($owner);
        $this->client->request('GET', '/profile/purchase/' . $purchase->getReference());
        $this->assertResponseIsSuccessful();
    }

    public function testOtherUserCannotViewSomeoneElesesPurchase(): void
    {
        $owner = $this->createUser('owner2-' . uniqid() . '@test.local');
        $stranger = $this->createUser('stranger-' . uniqid() . '@test.local');
        $purchase = $this->createPurchase($owner);
        $this->em->flush();
        
        $this->client->loginUser($stranger);
        $this->client->request('GET', '/profile/purchase/' . $purchase->getReference());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnknownReferenceReturns404(): void
    {
        $user = $this->createUser('user3-' . uniqid() . '@test.local');
        $this->em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/profile/purchase/CMD-INEXISTANTE');
        $this->assertResponseStatusCodeSame(404);
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setIsVerified(true);
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $this->em->persist($user);

        return $user;
    }

    private function createPurchase(User $owner): Purchase
    {
        $purchase = new Purchase();
        $purchase->setUser($owner);
        $purchase->setReference('CMD-TEST-' . bin2hex(random_bytes(4)));
        $purchase->setStatus(PurchaseStatus::PAID);
        $purchase->setTotal('50.00');
        $purchase->setDelivery('45 rue du test, 76000 Rouen');
        $this->em->persist($purchase);
        $this->em->flush();

        return $purchase;
    }
}
