<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RequireVerifiedEmailSubscriber implements EventSubscriberInterface
{
    private const PROTECTED_ROUTES = ['checkout_index'];

    public function __construct(private Security $security, private RouterInterface $router)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (!in_array($route, self::PROTECTED_ROUTES, true)) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();
        if ($user && !$user->isVerified()) {
            $event->setResponse(new RedirectResponse($this->router->generate('app_purchase')));
        }
    }
}
