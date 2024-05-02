<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

readonly class BaseUrlService
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $port = $request->getPort();

        if ($port !== null && $port !== 80 && $port !== 443) {
            return sprintf('%s://%s:%s', $request->getScheme(), $request->getHost(), $port);
        }

        return sprintf('%s://%s', $request->getScheme(), $request->getHost());
    }
}