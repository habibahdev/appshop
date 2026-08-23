<?php

namespace App\Controller;

use App\Repository\StockRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(CartService $cartService, StockRepository $stockRepository): Response
    {
        $items = $cartService->getItems();

        $stocks = [];

        foreach ($items as $item) {
            $variantId = $item['variant']->getId();

            if ($variantId === null) {
                continue;
            }

            $stock = $stockRepository->findOneByVariant($item['variant']);

            $stocks[$variantId] = $stock?->getQty() ?? 0;
        }

        return $this->render('cart/index.html.twig', [
            'items' => $items,
            'stocks' => $stocks,
            'total' => $cartService->getTotal(),
        ]);
    }

    #[Route('/cart/add', name: 'app_cart_add', methods: ['POST'])]
    public function add(Request $request, CartService $cartService): Response
    {
        $variantId = $request->request->getInt('variant');
        $qty = max(1, $request->request->getInt('qty', 1));

        try {
            $cartService->add($variantId, $qty);
            $this->addFlash('success', 'Produit ajouté au panier');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/update/{variantId}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $variantId, Request $request, CartService $cartService): Response
    {
        $cartService->updateQty($variantId, $request->request->getInt('qty', 1));
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/delete/{variantId}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $variantId, CartService $cartService): Response
    {
        $cartService->remove($variantId);
        return $this->redirectToRoute('app_cart');
    }
}
