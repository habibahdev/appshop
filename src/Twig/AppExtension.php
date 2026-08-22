<?php

namespace App\Twig;

use App\Repository\ReviewRepository;
use App\Service\CartService;
use App\Service\CouponService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private CartService $cartService,
        private CouponService $couponService,
        private ReviewRepository $reviewRepository
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_total_qty', [$this->cartService, 'getTotalQty']),
            new TwigFunction('coupon_applied', [$this->couponService, 'getApplied']),
            new TwigFunction('coupon_discount', [$this->couponService, 'calculateDiscount']),
            new TwigFunction('review_average', [$this->reviewRepository, 'getAverageRating']),
            new TwigFunction('review_list', [$this->reviewRepository, 'findApprovedForProduct'])
        ];
    }
}
