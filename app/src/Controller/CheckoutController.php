<?php

namespace App\Controller;

use App\DTO\CheckoutDetails;
use App\DTO\FreeCheckoutDTO;
use App\DTO\PaidCheckoutDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\OrderStatusEnum;
use App\Exception\FraudLabsProApiException;
use App\Exception\FraudLabsProRejectException;
use App\Exception\InvalidCheckoutInputException;
use App\Form\FreeCheckoutType;
use App\Form\PaidCheckoutType;
use App\Service\CartService;
use App\Service\CheckoutService;
use App\Service\FraudDetectionService;
use App\Service\StripeService;
use App\Service\ThumbnailService;
use App\ValueObject\Money;
use DateTimeImmutable;
use ReflectionException;
use Stripe\Exception\ApiErrorException;
use Doctrine\ORM\EntityManagerInterface;
use CountryEnums\Exceptions\EnumNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/checkout', name: 'app_checkout_')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        CartService $cartService,
        ThumbnailService $thumbnailService
    ): Response
    {
        $cart = $cartService->get();
        $total = $cart['total'];
        $items = $cart['items'];

        $checkoutDetails = new CheckoutDetails(email: $this->getUser()->getEmail());

        if ($total == 0) {
            $form = $this->createForm(FreeCheckoutType::class, new FreeCheckoutDTO(checkoutDetails: $checkoutDetails));
        } else {
            $form = $this->createForm(PaidCheckoutType::class, new PaidCheckoutDTO(checkoutDetails: $checkoutDetails));
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

    #[Route('/create/payment-intent', name: 'create_pi', methods: ['POST'])]
    public function createPaymentIntent(
        CartService $cartService,
        StripeService $stripeService,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        CheckoutService $checkoutService,
        FraudDetectionService $fraudDetectionService
    ): Response
    {
        $data = json_decode($request->getContent(), true);

        /** first param is the form name 'checkout' */
        if (!$this->isCsrfTokenValid('paid_checkout', $data['_token'])) {
            return new JsonResponse('Invalid CSRF token', Response::HTTP_FORBIDDEN);
        }

        $cart = $cartService->get();
        $total = $cart['total'];
        $money = new Money($total);
        $cents = $money->toCents();

        if ($cents == 0) {
            return new JsonResponse("Only paid orders", Response::HTTP_FORBIDDEN);
        }

        if ($cents < 50) {
            return new JsonResponse("Only orders that are {$this->getParameter('app_currency_symbol')}0.5 or larger allowed.", Response::HTTP_FORBIDDEN);
        }

        try {
            $checkoutDetails = CheckoutDetails::createFromArray($data);
            $checkoutDTO = PaidCheckoutDTO::createFromRequest($checkoutDetails, $data);
            $checkoutService->validateForm($checkoutDTO);
            $fraudDetectionService->validate($checkoutDTO, $total, $cart['quantity'], $cart['currency']);
            $pm = $stripeService->createPaymentMethod($checkoutDTO);
            $pi = $stripeService->createPaymentIntent($pm, $money, $this->getParameter('app_currency'), $checkoutDTO->getEmail());
            $csrfToken = $csrfTokenManager->refreshToken('paid_checkout')->getValue();

            return $this->json([
                'id' => $pi->id,
                'payment_method' => $pi->payment_method,
                'status' => $pi->status,
                'client_secret' => $pi->client_secret,
                'csrfToken' => $csrfToken
            ]);
        } catch (EnumNotFoundException $e) {
            return $this->json([
                'input_errors' => [
                    'country' => $e->getMessage()
                ]
            ], Response::HTTP_BAD_REQUEST);
        } catch (InvalidCheckoutInputException $e) {
            return $this->json([
                'input_errors' => $e->getErrors()
            ], Response::HTTP_BAD_REQUEST);
        } catch (ReflectionException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (FraudLabsProApiException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (FraudLabsProRejectException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (ApiErrorException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/complete/paid', name: 'complete_paid', methods: ['POST'])]
    public function completePaid(
        CartService $cartService,
        Request $request,
        EntityManagerInterface $entityManager,
        StripeService $stripeService
    ): Response
    {
        $token = $request->request->get('_token');

        /** first param is the form name 'checkout' */
        if (!$this->isCsrfTokenValid('paid_checkout', $token)) {
            return new JsonResponse('Invalid CSRF token', Response::HTTP_FORBIDDEN);
        }

        $piId = $request->request->get('pi');

        try {
            $pi = $stripeService->getPaymentIntent($piId);
            $pm = $stripeService->getPaymentMethod($pi->payment_method);

            $cart = $cartService->get();
            $total = $cart['total'];
            $items = $cart['items'];

            if ($pi->metadata['cart_hash'] !== $cart['hash']) {
                return $this->json([
                    'message' => 'Invalid cart hash'
                ], Response::HTTP_BAD_REQUEST);
            }

            $metadata = $pm->metadata->toArray();
            $metadata['total'] = $total;
            $metadata['currency'] = $this->getParameter('app_currency');
            $metadata['createdAt'] = new DateTimeImmutable();
            $metadata['updatedAt'] = new DateTimeImmutable();
            $metadata['user'] = $items[0]['product']->getUser();
            $metadata['customer'] = $this->getUser();
            $metadata['paymentMetadata'] = [
                'gateway' => 'stripe',
                'payment_intent' => $pi->id,
                'payment_method' => $pm->id,
            ];
            $metadata['status'] = OrderStatusEnum::COMPLETED;
            $order = Order::createFromMetadata($metadata);

            $entityManager->persist($order);
            $entityManager->flush();

            foreach ($items as $item) {
                $orderItem = new OrderItem();
                $orderItem->setName($item['product']->getName())
                    ->setProduct($item['product'])
                    ->setOrder($order)
                    ->setQuantity($item['quantity'])
                    ->setPrice($item['product']->getSalePrice())
                    ->setCreatedAt(new DateTimeImmutable())
                    ->setUpdatedAt(new DateTimeImmutable());
                $entityManager->persist($orderItem);
            }

            $entityManager->flush();
            $cartService->clear();

            return $this->json([
                'uid' => $order->getUniqueId(),
            ]);
        } catch (ApiErrorException $e) {
            return $this->json([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/complete/free', name: 'complete_free', methods: ['POST'])]
    public function completeFree(
        CheckoutService $checkoutService,
        CartService $cartService,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $data = json_decode($request->getContent(), true);

        /** first param is the form name 'checkout' */
        if (!$this->isCsrfTokenValid('free_checkout', $data['_token'])) {
            return new JsonResponse('Invalid CSRF token', Response::HTTP_FORBIDDEN);
        }

        $cart = $cartService->get();
        $total = $cart['total'];
        $items = $cart['items'];
        $money = new Money($total);

        if ($money->toCents() > 0) {
            return new JsonResponse("Only free orders allowed", Response::HTTP_FORBIDDEN);
        }

        try {
            $checkoutDetails = CheckoutDetails::createFromArray($data);
            $checkoutDTO = FreeCheckoutDTO::createFromRequest($checkoutDetails, $data);
            $checkoutService->validateForm($checkoutDTO);

            $metadata = [];
            $metadata['firstName'] = $checkoutDTO->getFirstName();
            $metadata['lastName'] = $checkoutDTO->getLastName();
            $metadata['email'] = $checkoutDTO->getEmail();
            $metadata['country'] = $checkoutDTO->getCountry()->value;
            $metadata['city'] = $checkoutDTO->getCity();
            $metadata['zipCode'] = $checkoutDTO->getZipCode();
            $metadata['address'] = $checkoutDTO->getAddress();
            $metadata['total'] = 0;
            $metadata['currency'] = $this->getParameter('app_currency');
            $metadata['createdAt'] = new DateTimeImmutable();
            $metadata['updatedAt'] = new DateTimeImmutable();
            $metadata['user'] = $items[0]['product']->getUser();
            $metadata['customer'] = $this->getUser();
            $metadata['paymentMetadata'] = [];
            $metadata['status'] = OrderStatusEnum::COMPLETED;
            $order = Order::createFromMetadata($metadata);

            $entityManager->persist($order);
            $entityManager->flush();

            foreach ($items as $item) {
                $orderItem = new OrderItem();
                $orderItem->setName($item['product']->getName())
                    ->setProduct($item['product'])
                    ->setOrder($order)
                    ->setQuantity($item['quantity'])
                    ->setPrice($item['product']->getSalePrice())
                    ->setCreatedAt(new DateTimeImmutable())
                    ->setUpdatedAt(new DateTimeImmutable());
                $entityManager->persist($orderItem);
            }

            $entityManager->flush();
            $cartService->clear();

            return $this->json([
                'uid' => $order->getUniqueId(),
            ]);
        } catch (EnumNotFoundException $e) {
            return $this->json([
                'input_errors' => [
                    'country' => $e->getMessage()
                ]
            ], Response::HTTP_BAD_REQUEST);
        } catch (InvalidCheckoutInputException $e) {
            return $this->json([
                'input_errors' => $e->getErrors()
            ], Response::HTTP_BAD_REQUEST);
        } catch (ReflectionException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
