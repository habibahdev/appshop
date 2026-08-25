<?php

namespace App\Controller;

use Stripe\Checkout\Session;
use App\Service\StripeService;
use App\Message\ProcessStripeWebhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StripeWebhookController extends AbstractController
{
    public function __construct(private readonly string $stripeWebhookSecret)
    {
    }

    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function handle(
        Request $request,
        StripeService $stripeService,
        MessageBusInterface $bus
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        try {
            $event = $stripeService->constructWebhookEvent($payload, $signature, $this->stripeWebhookSecret);
        } catch (\UnexpectedValueException | SignatureVerificationException $e) {
            return new Response('Signature invalide', Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'checkout.session.completed') {
            /** @var Session $session */
            $session = $event->data->object;
            $bus->dispatch(new ProcessStripeWebhook(
                purchaseReference: $session->client_reference_id,
                paymentIntentId: $session->payment_intent
            ));
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
