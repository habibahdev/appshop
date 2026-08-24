<?php

namespace App\Controller;

use App\Repository\PurchaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

final class PurchaseController extends AbstractController
{
    #[Route('/my-purchases', name: 'app_purchase')]
    public function index(PurchaseRepository $purchaseRepository): Response
    {
        return $this->render('purchase/index.html.twig', [
            'purchases' => $purchaseRepository->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC'])
        ]);
    }

    #[Route('/purchase/{reference}', name: 'app_purchase_show')]
    public function show(
        string $reference,
        PurchaseRepository $purchaseRepository,
        WorkflowInterface $workflow
    ): Response {
        $purchase = $purchaseRepository->findOneBy(['reference' => $reference])
            ?? throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted('view', $purchase);

        return $this->render('purchase/show.html.twig', [
            'purchase' => $purchase,
            'availableTransitions' => $workflow->getEnabledTransitions($purchase)
        ]);
    }
}
