<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\StripeService;
use App\Service\PurchaseService;
use App\Repository\PurchaseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function index(Request $request, PurchaseService $purchaseService, StripeService $stripeService): Response
    {
        if ($request->isMethod('POST')) {
            $address = $request->request->get('address');

            /** @var User $user */
            $user = $this->getUser();
            try {
                $purchase = $purchaseService->createFromCart($user, (string) $address);
                $session = $stripeService->createCheckoutSession($purchase);

                return $this->redirect($session->url, 303);
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('app_cart');
            }
        }
        return $this->render('checkout/index.html.twig');
    }

    #[Route('/purchase/succes/{reference}', name: 'app_checkout_success')]
    public function success(string $reference, PurchaseRepository $purchaseRepository): Response
    {
        $purchase = $purchaseRepository->findOneBy(['reference' => $reference])
            ?? throw $this->createNotFoundException();

        return $this->render('checkout/success.html.twig', [
            'purchase' => $purchase
        ]);
    }

    #[Route('/purchase/cancel/{reference}', name: 'app_checkout_cancel')]
    public function cancel(string $reference): Response
    {
        return $this->render('checkout/cancel.html.twig', [
            'reference' => $reference
        ]);
    }
}
