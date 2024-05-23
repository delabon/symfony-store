<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_index')]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository,
        PageRepository $pageRepository,
        OrderRepository $orderRepository
    ): Response
    {
        $ordersTotals = $orderRepository->getTotals();

        return $this->render('admin/index.html.twig', [
            'productsCount' => $productRepository->count(),
            'categoriesCount' => $categoryRepository->count(),
            'usersCount' => $userRepository->count(),
            'pagesCount' => $pageRepository->count(),
            'ordersCount' => $orderRepository->count(),
            'total' => $ordersTotals['ordersTotal'],
            'totalRefunds' => $ordersTotals['totalRefunds'],
        ]);
    }
}
