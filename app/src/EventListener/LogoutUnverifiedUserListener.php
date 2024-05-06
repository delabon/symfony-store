<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\LogoutUserService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final readonly class LogoutUnverifiedUserListener
{
    public function __construct(
        private LogoutUserService $logoutUserService,
        private Security $security,
        private RouterInterface $router
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

        if ($user->isVerified()) {
            return;
        }

        $this->logoutUserService->handle();
        $event->getRequest()->getSession()->getFlashBag()->add('info', 'You need to verify your email.');

        $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
    }
}
