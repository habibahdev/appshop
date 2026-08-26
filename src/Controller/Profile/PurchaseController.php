<?php

namespace App\Controller\Profile;

use App\Repository\PurchaseRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class PurchaseController extends AbstractController
{
    #[Route('/profile/my-purchases', name: 'app_purchases')]
    public function index(PurchaseRepository $purchaseRepository): Response
    {
        return $this->render('profile/purchase/index.html.twig', [
            'purchases' => $purchaseRepository->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC'])
        ]);
    }

    #[Route('/profile/purchase/{reference}', name: 'app_purchase_show')]
    public function show(
        string $reference,
        PurchaseRepository $purchaseRepository,
        #[Target('purchase_status')]
        WorkflowInterface $workflow
    ): Response {
        $purchase = $purchaseRepository->findOneBy(['reference' => $reference])
            ?? throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted('view', $purchase);

        return $this->render('profile/purchase/show.html.twig', [
            'purchase' => $purchase,
            'availableTransitions' => $workflow->getEnabledTransitions($purchase)
        ]);
    }
}
