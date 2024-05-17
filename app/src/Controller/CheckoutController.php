<?php

namespace App\Controller;

use App\DTO\CheckoutDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\OrderStatusEnum;
use App\Form\CheckoutType;
use App\Service\CartService;
use App\Service\StripeService;
use App\Service\ThumbnailService;
use App\ValueObject\Money;
use DateTimeImmutable;
use Stripe\Exception\ApiErrorException;
use Doctrine\ORM\EntityManagerInterface;
use CountryEnums\Exceptions\EnumNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_USER')]
#[Route('/checkout', name: 'app_checkout_')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(CartService $cartService, ThumbnailService $thumbnailService): Response
    {
        $form = $this->createForm(CheckoutType::class, new CheckoutDTO(email: $this->getUser()->getEmail()));
        $cart = $cartService->get();
        $total = $cart['total'];
        $items = $cart['items'];
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
        ValidatorInterface $validator,
        Request $request
    ): Response
    {
        $data = $request->request->all()['checkout'];

        /** first param is the form name 'checkout' */
        if (!$this->isCsrfTokenValid('checkout', $data['_token'])) {
            return new JsonResponse('Invalid CSRF token', Response::HTTP_FORBIDDEN);
        }

        $cart = $cartService->get();
        $total = $cart['total'];
        $money = new Money($total);

        try {
            $checkoutDTO = CheckoutDTO::createFromRequest($data);
        } catch (EnumNotFoundException $e) {
            return $this->json(['input_errors' => [
                'country' => $e->getMessage()
            ]], Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($checkoutDTO);

        if (count($errors)) {
            $returnErrors = [];

            foreach ($errors as $error) {
                /** @var ConstraintViolation $error */
                $returnErrors[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['input_errors' => $returnErrors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $pm = $stripeService->createPaymentMethod($checkoutDTO);
            $pi = $stripeService->createPaymentIntent($pm, $money, $this->getParameter('app_currency'), $checkoutDTO->getEmail());

            return $this->json([
                'id' => $pi->id,
                'payment_method' => $pi->payment_method,
                'status' => $pi->status,
                'next_action' => $pi->next_action,
                'client_secret' => $pi->client_secret,
            ]);
        } catch (ApiErrorException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/complete', name: 'complete', methods: ['POST'])]
    public function complete(CartService $cartService, Request $request, EntityManagerInterface $entityManager, StripeService $stripeService): Response
    {
        $token = $request->request->get('_token');

        /** first param is the form name 'checkout' */
        if (!$this->isCsrfTokenValid('checkout', $token)) {
            return new JsonResponse('Invalid CSRF token', Response::HTTP_FORBIDDEN);
        }

        $piId = $request->request->get('pi');

        try {
            $pi = $stripeService->getPaymentIntent($piId);
            $pm = $stripeService->getPaymentMethod($pi->payment_method);
        } catch (ApiErrorException $e) {
            return $this->json([
                'errors' => [
                    $e->getMessage()
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $cart = $cartService->get();
        $total = $cart['total'];
        $items = $cart['items'];
        $cartHash = $cart['hash'];

        if ($pi->metadata['cart_hash'] !== $cartHash) {
            return $this->json([
                'errors' => [
                    'Invalid cart hash'
                ]
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
    }
}
