<?php

namespace App\Service;

use App\DTO\PaidCheckoutDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\OrderStatusEnum;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Utility\StringToFloatUtility;
use App\ValueObject\Money;
use DateTime;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Refund;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class StripeService
{
    private ?StripeClient $client;

    public function __construct(
        #[Autowire('%stripe_secret_key%')]
        private readonly string $stripeSecretKey,
        #[Autowire('%app_refund_days%')]
        private readonly string $refundDays,
        private readonly CartService $cartService,
        private readonly OrderRepository $orderRepository,
        private readonly OrderItemRepository $orderItemRepository
    )
    {
        $this->client = new StripeClient($this->stripeSecretKey);
    }

    /**
     * @param PaidCheckoutDTO $checkoutDTO
     * @return PaymentMethod
     * @throws ApiErrorException
     */
    public function createPaymentMethod(PaidCheckoutDTO $checkoutDTO): PaymentMethod
    {
        return $this->client->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => $checkoutDTO->getCcNumber(),
                'exp_month' => explode('/', $checkoutDTO->getCcDate())[0],
                'exp_year' => explode('/', $checkoutDTO->getCcDate())[1],
                'cvc' => $checkoutDTO->getCcCvc(),
            ],
            'billing_details' => [
                'name' => $checkoutDTO->getFirstName() . ' ' . $checkoutDTO->getLastName(),
                'email' => $checkoutDTO->getEmail(),
                'address' => [
                    'country' => $checkoutDTO->getCountry()->value,
                    'city' => $checkoutDTO->getCity(),
                    'postal_code' => $checkoutDTO->getZipCode(),
                    'line1' => $checkoutDTO->getAddress(),
                ],
            ],
            'metadata' => [
                'firstName' => $checkoutDTO->getFirstName(),
                'lastName' => $checkoutDTO->getLastName(),
                'email' => $checkoutDTO->getEmail(),
                'country' => $checkoutDTO->getCountry()->value,
                'city' => $checkoutDTO->getCity(),
                'zipCode' => $checkoutDTO->getZipCode(),
                'address' => $checkoutDTO->getAddress(),
            ]
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentIntent(PaymentMethod $paymentMethod, Money $money, string $currency, string $email): PaymentIntent
    {
        return $this->client->paymentIntents->create([
            'amount' => $money->toCents(),
            'currency' => strtolower($currency),
            'payment_method' => $paymentMethod->id,
            'receipt_email' => $email,
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never'
            ],
            'confirm' => true,
            'metadata' => [
                'cart_hash' => $this->cartService->get()['hash']
            ]
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function getPaymentIntent(string $id): PaymentIntent
    {
        return $this->client->paymentIntents->retrieve($id);
    }

    /**
     * @throws ApiErrorException
     */
    public function getPaymentMethod(string $id): PaymentMethod
    {
        return $this->client->paymentMethods->retrieve($id);
    }

    /**
     * @throws ApiErrorException
     */
    public function fullRefund(Order $order): void
    {
        if ($order->getStatus() === OrderStatusEnum::REFUNDED) {
            throw new LogicException('You already refunded this order.');
        }

        $amountToRefund = StringToFloatUtility::convert($order->getTotal()) - StringToFloatUtility::convert($order->getTotalRefunded());

        if ($amountToRefund == 0) {
            throw new LogicException('You cannot refund a free order.');
        }

        if (!$this->isInRefundPeriod($order, $this->refundDays)) {
            throw new LogicException('You cannot refund this order. The refund period has expired.');
        }

        $paymentMetadata = $order->getPaymentMetadata();
        $this->validatePaymentMethod($paymentMetadata);
        $this->refund($amountToRefund, $paymentMetadata['payment_intent']);

        $order->setStatus(OrderStatusEnum::REFUNDED);
        $order->setTotalRefunded($amountToRefund);

        foreach ($order->getItems() as $item) {
            /** @var OrderItem $item */
            $item->setRefunded(true);
            $this->orderItemRepository->save($item);
        }

        $this->orderRepository->save($order);
    }

    /**
     * @throws ApiErrorException
     */
    public function partialRefund(OrderItem $item): void
    {
        if ($item->isRefunded()) {
            throw new LogicException('This item has already been refunded.', Response::HTTP_FORBIDDEN);
        }

        $amountToRefund = StringToFloatUtility::convert($item->getPrice());

        if ($amountToRefund == 0) {
            throw new LogicException('You cannot refund free items', Response::HTTP_FORBIDDEN);
        }

        $order = $item->getOrder();

        if ($order->getStatus() === OrderStatusEnum::REFUNDED) {
            throw new LogicException('This order has already been refunded.', Response::HTTP_FORBIDDEN);
        }

        if (!$this->isInRefundPeriod($order, $this->refundDays)) {
            throw new LogicException('You cannot refund this item. The refund period has expired.');
        }

        $paymentMetadata = $order->getPaymentMetadata();
        $this->validatePaymentMethod($paymentMetadata);
        $this->refund($amountToRefund, $paymentMetadata['payment_intent']);

        $item->setRefunded(true);
        $this->orderItemRepository->save($item);

        $orderTotal = StringToFloatUtility::convert($order->getTotal());
        $orderTotalRefunded = StringToFloatUtility::convert($order->getTotalRefunded());

        if ($orderTotalRefunded + $amountToRefund == $orderTotal) {
            $order->setStatus(OrderStatusEnum::REFUNDED);
            $order->setTotalRefunded($orderTotal);
        } else {
            $order->setStatus(OrderStatusEnum::PARTIAL_REFUNDED);
            $order->setTotalRefunded($orderTotalRefunded + $amountToRefund);
        }

        $this->orderRepository->save($order);
    }

    private function validatePaymentMethod(array $paymentMetadata): void
    {
        $gateway = $paymentMetadata['gateway'] ?? null;

        if ($gateway !== 'stripe') {
            throw new InvalidArgumentException('Invalid gateway.');
        }

        $pi = $paymentMetadata['payment_intent'] ?? null;

        if (!$pi) {
            throw new InvalidArgumentException('Invalid Stripe payment intent.');
        }
    }

    /**
     * @throws ApiErrorException
     */
    private function refund(float $amountToRefund, string $pi): Refund
    {
        $money = new Money($amountToRefund);
        $refund = $this->client->refunds->create([
            'payment_intent' => $pi,
            'amount' => $money->toCents(),
            'reason' => 'requested_by_customer',
        ]);

        if ($refund->status === 'pending' || $refund->status === 'requires_action') {
            throw new RuntimeException('The refund is pending or requires action.', Response::HTTP_ACCEPTED);
        } else if ($refund->status === 'failed') {
            throw new RuntimeException('The refund has failed.', Response::HTTP_PAYMENT_REQUIRED);
        } else if ($refund->status === 'canceled') {
            throw new RuntimeException('The refund was canceled.', Response::HTTP_EXPECTATION_FAILED);
        }

        return $refund;
    }

    public function isInRefundPeriod(Order $order, int $refundDays): bool
    {
        $now = new DateTime();
        $refundLastDay = (new DateTime())->setTimestamp($order->getCreatedAt()->getTimestamp());
        $refundLastDay->modify('+ ' . $refundDays . ' days');

        if ($now > $refundLastDay) {
            return false;
        }

        return true;
    }
}