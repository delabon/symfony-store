<?php

namespace App\DTO;

use CountryEnums\Country;
use CountryEnums\Exceptions\EnumNotFoundException;
use Symfony\Component\Validator\Constraints AS Assert;

class CheckoutDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        #[Assert\Regex(pattern: '/^[a-z ]+$/i')]
        public string $firstName = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        #[Assert\Regex(pattern: '/^[a-z ]+$/i')]
        public string $lastName = '',

        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 255)]
        public string $address = '',

        #[Assert\NotBlank]
        #[Assert\Choice(callback: [Country::class, 'cases'], message: 'Invalid country')]
        public Country $country = Country::US,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public string $city = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        #[Assert\Regex(pattern: '/^[0-9a-z][0-9a-z\- ]+[0-9a-z]$/i', message: 'Invalid zip code')]
        public string $zipCode = '',

        #[Assert\NotBlank]
        #[Assert\CardScheme(schemes: ['MASTERCARD', 'VISA', 'DISCOVER', 'JCB', 'AMEX', 'CHINA_UNIONPAY', 'MAESTRO'])]
        public string $ccNumber = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 5)]
        #[Assert\Regex(pattern: '/^[0-1][0-9]\/[0-3][0-9]$/i', message: 'Invalid expiration date')]
        public string $ccDate = '',

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[0-9]{3,4}$/', message: 'Invalid CVC  code')]
        public string $ccCvc = '',
    ) {}

    /**
     * @param array $data
     * @return self
     * @throws EnumNotFoundException
     */
    public static function createFromRequest(array $data): self
    {
        $checkoutDTO = new self();
        $props = get_class_vars(CheckoutDTO::class);

        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $props)) {
                continue;
            }

            if ($key === 'country') {
                $checkoutDTO->country = Country::parse($value);
            } else {
                $checkoutDTO->$key = $value;
            }
        }

        return $checkoutDTO;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function getCcNumber(): string
    {
        return $this->ccNumber;
    }

    public function getCcDate(): string
    {
        return $this->ccDate;
    }

    public function getCcCvc(): string
    {
        return $this->ccCvc;
    }
}