<?php

namespace App\Twig;

use App\Repository\ReviewRepository;
use App\Service\CartService;
use App\Service\CouponService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private const COLOR_MAP = [
        'noir' => '#1C1C1A',
        'blanc' => '#FFFFFF',
        'bleu' => '#2563EB',
        'rouge' => '#DC2626',
        'vert' => '#16A34A',
        'jaune' => '#EAB308',
        'gris' => '#78716C',
        'beige' => '#D6C7B0',
        'marron' => '#78350F',
        'rose' => '#EC4899',
        'violet' => '#7C3AED',
        'orange' => '#EA580C',
    ];

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
            new TwigFunction('review_list', [$this->reviewRepository, 'findApprovedForProduct']),
            new TwigFunction('color_hex', [$this, 'colorHex'])
        ];
    }

    public function colorhex(string $name): string
    {
        return self::COLOR_MAP[mb_strtolower($name)] ?? '#A8A29E';
    }
}
