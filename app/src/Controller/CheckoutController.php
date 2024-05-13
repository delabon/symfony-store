<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\CheckoutType;
use App\Service\CartService;
use App\Service\ThumbnailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout_index')]
    public function index(CartService $cartService, ThumbnailService $thumbnailService, Request $request, EntityManagerInterface $entityManager): Response
    {
        $cart = $cartService->get();
        $total = $cart['total'];
        $items = $cart['items'];

        $order = new Order();
        $order->setEmail($this->getUser()->getEmail());

        $form = $this->createForm(CheckoutType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order = $form->getData();
            $entityManager->persist($order);
            $entityManager->flush();

            $cartService->clear();

            $this->addFlash('success', 'Thank you for your purchase.');

            return $this->redirectToRoute('app_checkout_index');
        }

        $thumbnails = [];

        foreach ($items as $item) {
            $thumbnails[$item['product']->getId()] = $thumbnailService->getUrl($item['product']->getThumbnailId() ?: 0, 300, 300);
        }

        return $this->render('checkout/index.html.twig', [
            'total' => $total,
            'items' => $items,
            'thumbnails' => $thumbnails,
            'form' => $form
        ]);
    }
}
