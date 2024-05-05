<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class LogoutUserService
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack
    )
    {
    }

    public function handle(): void
    {
        $this->requestStack->getSession()->invalidate();
        $this->security->logout(false);
    }
}