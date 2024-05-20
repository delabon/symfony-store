<?php

namespace App\Abstract;

use App\DTO\CheckoutDetails;
use CountryEnums\Country;
use ReflectionClass;
use ReflectionException;

readonly abstract class AbstractCheckoutDTO
{
    /**
     * @param CheckoutDetails $checkoutDetails
     * @param array $data
     * @return static
     * @throws ReflectionException
     */
    public static function createFromRequest(CheckoutDetails $checkoutDetails, array $data): static
    {
        $reflector = new ReflectionClass(static::class);
        $instance = $reflector->newInstanceWithoutConstructor();

        $prop = $reflector->getProperty('checkoutDetails');
        $prop->setValue($instance, $checkoutDetails);

        foreach ($data as $key => $value) {
            if (!$reflector->hasProperty($key)) {
                continue;
            }

            $prop = $reflector->getProperty($key);
            $prop->setValue($instance, $value);
        }

        return $instance;
    }

    public function getFirstName(): string
    {
        return $this->checkoutDetails->getFirstName();
    }

    public function getLastName(): string
    {
        return $this->checkoutDetails->getLastName();
    }

    public function getEmail(): string
    {
        return $this->checkoutDetails->getEmail();
    }

    public function getAddress(): string
    {
        return $this->checkoutDetails->getAddress();
    }

    public function getCountry(): Country
    {
        return $this->checkoutDetails->getCountry();
    }

    public function getCity(): string
    {
        return $this->checkoutDetails->getCity();
    }

    public function getZipCode(): string
    {
        return $this->checkoutDetails->getZipCode();
    }

    public function getCheckoutDetails(): CheckoutDetails
    {
        return $this->checkoutDetails;
    }
}