<?php

namespace App\DTO;

use CountryEnums\Country;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Validator\Constraints AS Assert;

final readonly class CheckoutDetails
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
    ) {}

    /**
     * @param array $data
     * @return static
     * @throws ReflectionException
     */
    public static function createFromArray(array $data): self
    {
        $reflector = new ReflectionClass(self::class);
        $instance = $reflector->newInstanceWithoutConstructor();

        foreach ($data as $key => $value) {
            if (!$reflector->hasProperty($key)) {
                continue;
            }

            $prop = $reflector->getProperty($key);

            if ($key === 'country') {
                $prop->setValue($instance, Country::parse($value));
            } else {
                $prop->setValue($instance, $value);
            }
        }

        return $instance;
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
}