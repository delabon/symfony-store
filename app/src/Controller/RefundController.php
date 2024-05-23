<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\StripeService;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/refund', name: 'app_refund_')]
class RefundController extends AbstractController
{
    #[Route('/full/{id<\d+>}', name: 'full', methods: ['GET'])]
    public function full(
        Order $order,
        StripeService $stripeService
    ): Response
    {
        if ($order->getCustomer() !== $this->getUser()) {
            $this->addFlash('danger', 'You can only refund your orders.');

            return $this->redirectToRoute('app_purchase_show', [
                'id' => $order->getId()
            ]);
        }

        try {
            $stripeService->fullRefund($order);
            $this->addFlash('success', 'Your order has been fully refunded.');

            return $this->redirectToRoute('app_purchase_show', [
                'id' => $order->getId()
            ]);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), Response::HTTP_FORBIDDEN);
        }  catch (RuntimeException $e) {
            return new Response($e->getMessage(), $e->getCode());
        } catch (ApiErrorException $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/item/{id<\d+>}', name: 'item', methods: ['GET'])]
    public function item(
        OrderItem $item,
        StripeService $stripeService
    ): Response
    {
        $order = $item->getOrder();

        if ($order->getCustomer() !== $this->getUser()) {
            $this->addFlash('danger', 'You can only refund your order items.');

            return $this->redirectToRoute('app_purchase_show', [
                'id' => $order->getId()
            ]);
        }

        try {
            $stripeService->partialRefund($item);
            $this->addFlash('success', 'Your refund has succeeded.');

            return $this->redirectToRoute('app_purchase_show', [
                'id' => $order->getId()
            ]);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), Response::HTTP_FORBIDDEN);
        }  catch (RuntimeException $e) {
            return new Response($e->getMessage(), $e->getCode());
        } catch (ApiErrorException $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
