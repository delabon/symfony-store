<?php

namespace App\EventListener;

use App\Entity\Cart;
use App\Entity\User;
use App\Repository\CartRepository;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

final class MergeCartWhenLoginListener
{

    public function __construct(
        private readonly CartRepository $cartRepository
    )
    {
    }

    #[AsEventListener(event: 'security.interactive_login')]
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $sessionCart = $event->getRequest()->getSession()->get('cart', []);
        $dbCart = $user->getCart();

        if (!$dbCart instanceof Cart) {
            $user->setCart(new Cart());
            $dbCart = $user->getCart();
            $dbCart->setCreatedAt(new DateTimeImmutable());
        }

        $dbCart->setUpdatedAt(new DateTimeImmutable());
        $dbCart->mergeItems($sessionCart);
        $this->cartRepository->save($dbCart);
    }
}
