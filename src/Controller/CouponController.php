<?php

namespace App\Controller;

use App\Service\CouponService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CouponController extends AbstractController
{
    #[Route('/cart/coupon', name: 'app_coupon_apply', methods: ['POST'])]
    public function apply(Request $request, CouponService $couponService): Response
    {
        try {
            $couponService->apply((string) $request->request->get('code', ''));
            $this->addFlash('success', 'Code promo appliqué');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/coupon/remove', name: 'app_coupon_remove', methods: ['POST'])]
    public function remove(CouponService $couponService): Response
    {
        $couponService->remove();

        return $this->redirectToRoute('app_cart');
    }
}
