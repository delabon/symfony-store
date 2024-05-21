<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/admin/orders', name: 'admin_order_index')]
    public function index(
        OrderRepository $orderRepository,
        Request $request
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = (int)$this->getParameter('app_admin_per_page');
        $paginator = $orderRepository->paginate($page, $limit);

        return $this->render('admin/order/index.html.twig', [
            'orders' => $paginator,
            'maxPages' => ceil($paginator->count() / $limit),
            'page' => $page,
        ]);
    }

    #[Route('/admin/orders/{id<\d+>}', name: 'admin_order_show')]
    public function show(Order $order): Response
    {
        return new Response();
    }

    #[Route('/admin/orders/{id<\d+>}/refund', name: 'admin_order_refund')]
    public function refund(Order $order): Response
    {
        return new Response();
    }
}
