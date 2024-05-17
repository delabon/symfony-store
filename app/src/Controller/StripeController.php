<?php

namespace App\Controller;

use App\Service\CartService;
use App\Service\StripeService;
use App\ValueObject\Money;
use Exception;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/stripe', name: 'ajax_stripe_')]
class StripeController extends AbstractController
{
    #[Route('/create/payment-method', name: 'create_payment_method', methods: ['POST'])]
    public function createPaymentMethod(Request $request, StripeService $stripeService, CartService $cartService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $cart = $cartService->get();
        $money = new Money($cart['total']);

        try {
            $pMethod = $stripeService->createPaymentMethod($data['cc_number'], $data['cc_exp_date'], $data['cc_cvc']);
            $pIntent = $stripeService->createPaymentIntent($pMethod, $money->toCents(), $this->getParameter('app_currency'), $data['email']);

            return $this->json([
                'id' => $pIntent->id,
                'payment_method' => $pIntent->payment_method,
                'status' => $pIntent->status,
                'next_action' => $pIntent->next_action,
                'client_secret' => $pIntent->client_secret,
            ]);
        } catch (ApiErrorException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
