<?php

namespace App\Twig;

use App\Repository\PageRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GetPagesExtension extends AbstractExtension
{
    public function __construct(private readonly PageRepository $pageRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getPages', [$this, 'getPages']),
        ];
    }

    public function getPages(): array
    {
        return $this->pageRepository->findPublicPages();
    }
}