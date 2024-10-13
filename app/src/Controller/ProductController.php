<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\ThumbnailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/product/{slug}', name: 'app_product_show')]
    public function show(Product $product, ThumbnailService $thumbnailService): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'thumbnail' => $product->getThumbnailId() ? $thumbnailService->getUrl($product->getThumbnailId()) : null,
        ]);
    }
}
