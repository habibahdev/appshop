<?php

namespace App\Service;

use App\Entity\Coupon;
use App\Enum\CouponType;
use App\Repository\CouponRepository;
use App\Util\Money;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Gestion des coupons de réduction appliqué au panier courant, stocké en
 * session (clé `coupon_code`).
 *
 * @package App\Service
 */
class CouponService
{
    private const SESSION_KEY = 'coupon_code';

    public function __construct(
        private CouponRepository $couponRepository,
        private RequestStack $requestStack,
        private CartService $cart
    ) {
    }

    /**
     * Valide et applique un code promo au panier courant.
     *
     * @param string $code Code du coupon (insensible à la casse).
     * @return Coupon Le coupon appliqué.
     * @throws \InvalidArgumentException Si le code est invalide, inactif,
     * expiré, épuisé, ou si le montant minimum du panuer n'est pas atteint.
     */
    public function apply(string $code): Coupon
    {
        $coupon = $this->couponRepository->findOneBy(['code' => strtoupper($code)]);

        if (!$coupon || !$coupon->isActive()) {
            throw new \InvalidArgumentException('Code promo invalide');
        }

        if ($coupon->isExpired()) {
            throw new \InvalidArgumentException('Ce code promo a expiré');
        }

        if ($coupon->isUsageLimitReached()) {
            throw new \InvalidArgumentException('Ce code promo a atteint sa limite d\'utilisation');
        }

        $minAmount = $coupon->getMinAmount();
        if (
            $minAmount !== null
                && bccomp(
                    $this->cart->getTotal(),
                    Money::assertNumericString(
                        $minAmount,
                        'montant minimum du coupon'
                    ),
                    2
                ) < 0
        ) {
            throw new \InvalidArgumentException(sprintf('Montant minimum requis: %s€', $minAmount));
        }

        $this->getSession()->set(self::SESSION_KEY, $coupon->getCode());

        return $coupon;
    }

    /**
     * Retire le coupon appliqué de la session.
     *
     * @return void
     */
    public function remove(): void
    {
        $this->getSession()->remove(self::SESSION_KEY);
    }

    /**
     * Coupon actuellement appliqué, revalidé à chaque leecture.
     * Un coupon devenu invalide entre-temps (expiration, épuisement) est
     * automatiquement retiré de la session avant retour.
     *
     * @return Coupon|null
     */
    public function getApplied(): ?Coupon
    {
        $code = $this->getSession()->get(self::SESSION_KEY);
        if (!$code) {
            return null;
        }

        $coupon = $this->couponRepository->findOneBy(['code' => $code]);
        if (!$coupon || !$coupon->isActive() || $coupon->isExpired() || $coupon->isUsageLimitReached()) {
            $this->remove();
            return null;
        }

        return $coupon;
    }

    /**
     * Calcule le montant de réduction à appliquer au panier.
     *
     * @param string $cartTotal Total du panier avant réduction.
     * @param Coupon|null $coupon Coupon à évaluer ; si null, utilise {@see getApplied()}
     * @return numeric-string Montant de la réduction, jamais supérieur à $cartTotal.
     */
    public function calculateDiscount(string $cartTotal, ?Coupon $coupon = null): string
    {
        $coupon ??= $this->getApplied();
        if (!$coupon) {
            return '0.00';
        }

        $total = Money::assertNumericString($cartTotal, 'total du panier');
        $value = Money::assertNumericString($coupon->getValue(), 'value du coupon');

        if ($coupon->getType() === CouponType::PERCENTAGE) {
            $discount = bcmul($total, bcdiv($value, '100', 4), 2);
        } else {
            $discount = $value;
        }

        return bccomp($discount, $total, 2) > 0 ? $total : $discount;
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
