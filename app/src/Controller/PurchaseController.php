<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/purchases', name: 'app_purchase_')]
class PurchaseController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        OrderRepository $orderRepository
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = (int)$this->getParameter('app_admin_per_page');

        try {
            $paginator = $orderRepository->paginateByCustomer($page, $limit, $this->getUser());
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->render('purchase/index.html.twig', [
            'orders' => $paginator,
            'maxPages' => ceil($paginator->count() / $limit),
            'page' => $page
        ]);
    }
}
