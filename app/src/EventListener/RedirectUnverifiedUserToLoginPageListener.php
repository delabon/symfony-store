<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\LogoutUserService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class RedirectUnverifiedUserToLoginPageListener
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator
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

        if ($event->getRequest()->getPathInfo() === $this->urlGenerator->generate('app_logout')) {
            return;
        }

        if ($event->getRequest()->getPathInfo() === $this->urlGenerator->generate('app_verify_email')) {
            return;
        }

        if ($event->getRequest()->getPathInfo() === $this->urlGenerator->generate('app_login')) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
    }
}
