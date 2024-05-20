<?php

namespace App\Service;

use App\DTO\PaidCheckoutDTO;
use App\ValueObject\Money;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class StripeService
{
    private ?StripeClient $client;

    public function __construct(
        #[Autowire('%stripe_secret_key%')]
        private readonly string $stripeSecretKey,
        private readonly CartService $cartService
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
}