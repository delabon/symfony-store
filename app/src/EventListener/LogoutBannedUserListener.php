<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LogoutBannedUserListener
{
    public function __construct(
        private readonly Security $security
    )
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (!$user->isBanned()) {
            return;
        }

        $event->getRequest()->getSession()->invalidate();
        $this->security->logout(false);
        $event->getRequest()->getSession()->getFlashBag()->add('warning', 'You are banned.');
    }
}
