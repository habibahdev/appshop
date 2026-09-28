<?php

namespace App\Tests\Functional;

use App\Entity\Purchase;
use App\Enum\PurchaseStatus;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class PurchaseWorkflowTest extends WebTestCase
{
    public function testCannotSkipFromPendingToShipped(): void
    {
        $workflow = $this->getWorkflow();
        $purchase = new Purchase();
        $purchase->setStatus(PurchaseStatus::PENDING);
        $this->assertFalse($workflow->can($purchase, 'expedier'));
        $this->expectException(LogicException::class);
        $workflow->apply($purchase, 'expedier');
    }

    public function testFullHappyPathTransitions(): void
    {
        $workflow = $this->getWorkflow();
        $purchase = new Purchase();
        $workflow->apply($purchase, 'payer');
        $this->assertSame(PurchaseStatus::PAID, $purchase->getStatus());

        $workflow->apply($purchase, 'preparer');
        $this->assertSame(PurchaseStatus::PREPARATION, $purchase->getStatus());

        $workflow->apply($purchase, 'expedier');
        $this->assertSame(PurchaseStatus::SHIPPED, $purchase->getStatus());

        $workflow->apply($purchase, 'livrer');
        $this->assertSame(PurchaseStatus::DELIVERED, $purchase->getStatus());
    }

    public function testCancelledPurchaseCannotBeShipped(): void
    {
        $workflow = $this->getWorkflow();
        $purchase = new Purchase();
        $purchase->setStatus(PurchaseStatus::CANCELLED);
        $this->assertFalse($workflow->can($purchase, 'expedier'));
        $this->assertFalse($workflow->can($purchase, 'preparer'));
    }

    private function getWorkflow(): WorkflowInterface
    {
        self::bootKernel();
        return static::getContainer()->get('state_machine.purchase_status');
    }
}
