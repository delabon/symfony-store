<?php

namespace App\Service;

use App\DTO\PaidCheckoutDTO;
use App\Exception\FraudLabsProApiException;
use App\Exception\FraudLabsProRejectException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use FraudLabsPro\FraudValidation;
use FraudLabsPro\Configuration;

class FraudDetectionService
{
    private FraudValidation $validator;

    public function __construct(
        #[Autowire('%fraudlabspro_api_key%')]
        private string $apiKey
    )
    {
        $this->validator = new FraudValidation(new Configuration($this->apiKey));
    }

    public function validate(PaidCheckoutDTO $checkoutDTO, float $total, int $quantity, string $currency): void
    {
        // Order details
        $orderDetails = [
            'order' => [
                // 'orderId' => '',
                // 'note' => 'Online shop',
                'currency' => strtoupper($currency),
                'amount' => $total,
                'quantity' => $quantity,

                // Please refer reference section for full list of payment methods
                'paymentMethod' => FraudValidation::CREDIT_CARD,
            ],

            'card' => [
                'number' => $checkoutDTO->getCcNumber(),
            ],

            'billing' => [
                'firstName' => $checkoutDTO->getFirstName(),
                'lastName' => $checkoutDTO->getLastName(),
                'email' => $checkoutDTO->getEmail(),
                // 'phone' => '',

                // 'state' => '',
                'address' => $checkoutDTO->getAddress(),
                'city' => $checkoutDTO->getCity(),
                'postcode' => $checkoutDTO->getZipCode(),
                'country' => $checkoutDTO->getCountry()->value,
            ],

            'shipping' => [
                // 'state' => '',
                'address' => $checkoutDTO->getAddress(),
                'city' => $checkoutDTO->getCity(),
                'postcode' => $checkoutDTO->getZipCode(),
                'country' => $checkoutDTO->getCountry()->value,
            ],
        ];

        // Sends the order details to FraudLabs Pro
        $result = $this->validator->validate($orderDetails);

        if (!$result) {
            throw new FraudLabsProApiException();
        }

        if ($result->fraudlabspro_status !== FraudValidation::APPROVE) {
            throw new FraudLabsProRejectException();
        }
    }
}