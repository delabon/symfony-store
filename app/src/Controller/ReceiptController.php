<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReceiptController extends AbstractController
{
    #[Route('/receipt/{uid}', name: 'app_receipt_show')]
    public function show(
        string $uid,
        OrderRepository $orderRepository
    ): Response
    {
        $order = $orderRepository->findOneBy(['uniqueId' => $uid]);

        if (!$order) {
            return new Response('No receipt found', Response::HTTP_NOT_FOUND);
        }

        return $this->render('receipt/show.html.twig', [
            'order' => $order,
        ]);
    }
}
