<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ThumbnailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ThumbnailService $thumbnailService
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = (int)$this->getParameter('app_per_page');
        $categorySlug = $request->query->get('category');
        $category = null;
        $thumbnails = [];

        if ($categorySlug) {
            $category = $categoryRepository->findOneBy(['slug' => $categorySlug]);
        }

        if ($category) {
            $paginator = $productRepository->paginateByCategory($category, $page, $limit);
        } else {
            $paginator = $productRepository->paginate($page, $limit);
        }

        foreach ($paginator as $product) {
            $thumbnails[$product->getId()] = $thumbnailService->getUrl($product->getThumbnailId() ?: 0, 500, 500);
        }

        return $this->render('home/index.html.twig', [
            'products' => $paginator,
            'page' => $page,
            'maxPages' => ceil($paginator->count() / $limit),
            'thumbnails' => $thumbnails,
            'categories' => $categoryRepository->nonEmptyCategories()
        ]);
    }
}
