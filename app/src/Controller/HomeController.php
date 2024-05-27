<?php

namespace App\Controller;

use App\Enum\StoreSortEnum;
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
        $sortEnum = StoreSortEnum::getByValue($request->query->get('sort', 'newest'));
        $page = $request->query->getInt('page', 1);
        $limit = (int)$this->getParameter('app_per_page');
        $search = $request->query->get('s');
        $categorySlug = $request->query->get('category');
        $category = null;
        $thumbnails = [];

        if (!empty($search)) {
            $paginator = $productRepository->paginatePublishedBySearch($search, $page, $limit, $sortEnum);
        } else {
            if ($categorySlug) {
                $category = $categoryRepository->findOneBy(['slug' => $categorySlug]);
            }

            if ($category) {
                $paginator = $productRepository->paginatePublishedByCategory($category, $page, $limit, $sortEnum);
            } else {
                $paginator = $productRepository->paginatePublished($page, $limit, $sortEnum);
            }
        }

        foreach ($paginator as $product) {
            $thumbnails[$product->getId()] = $thumbnailService->getUrl($product->getThumbnailId() ?: 0, 500, 500);
        }

        return $this->render('home/index.html.twig', [
            'products' => $paginator,
            'page' => $page,
            'maxPages' => ceil($paginator->count() / $limit),
            'thumbnails' => $thumbnails,
            'categories' => $categoryRepository->nonEmptyCategories(),
            'sortItems' => StoreSortEnum::toArray()
        ]);
    }
}
