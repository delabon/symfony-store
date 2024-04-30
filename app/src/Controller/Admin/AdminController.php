<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_index')]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository
    ): Response
    {
        return $this->render('admin/index.html.twig', [
            'productsCount' => $productRepository->count(),
            'categoriesCount' => $categoryRepository->count(),
            'usersCount' => $userRepository->count(),
        ]);
    }
}
