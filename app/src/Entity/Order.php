<?php

namespace App\Entity;

use App\DTO\CheckoutDetails;
use App\Enum\OrderStatusEnum;
use App\Repository\OrderRepository;
use App\ValueObject\Money;
use CountryEnums\Country;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stripe\PaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'customerOrders')]
    private ?User $customer = null;

    #[ORM\Column(length: 10)]
    private string $currency = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $total = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalRefunded = '0';

    #[ORM\Column(length: 255)]
    private OrderStatusEnum $status = OrderStatusEnum::PENDING;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255)]
    private string $firstName = '';

    #[ORM\Column(length: 255)]
    private string $lastName = '';

    #[ORM\Column(length: 255)]
    private string $email = '';

    #[ORM\Column(length: 255)]
    private string $address = '';

    #[ORM\Column(length: 50)]
    private string $zipCode = '';

    #[ORM\Column(length: 255)]
    private Country $country = Country::US;

    #[ORM\Column(length: 255)]
    private string $city = '';

    #[ORM\Column]
    private array $paymentMetadata = [];

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order')]
    private Collection $items;

    #[ORM\Column(unique: true)]
    private string $uniqueId = '';

    public static function createFromMetadata(array $metadata): self
    {
        $order = new self();
        $props = get_class_vars(self::class);

        foreach ($metadata as $key => $value) {
            if (!array_key_exists($key, $props)) {
                continue;
            }

            if ($key === 'country') {
                $order->$key = Country::parse($value);
            } else {
                $order->$key = $value;
            }
        }

        $order->generateUniqueId();

        return $order;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getTotalRefunded(): string
    {
        return $this->totalRefunded;
    }

    public function setTotalRefunded(string $totalRefunded): static
    {
        $this->totalRefunded = $totalRefunded;

        return $this;
    }

    public function getStatus(): OrderStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OrderStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    public function setCountry(Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function totalToCents(): int
    {
        $money = new Money($this->total);

        return $money->toCents();
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPaymentMetadata(): array
    {
        return $this->paymentMetadata;
    }

    public function setPaymentMetadata(array $paymentMetadata): static
    {
        $this->paymentMetadata = $paymentMetadata;

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function setItems(Collection $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    public function setUniqueId(string $uniqueId): void
    {
        $this->uniqueId = $uniqueId;
    }

    public function generateUniqueId(): string
    {
        $this->uniqueId = uniqid();

        return $this->uniqueId;
    }
}
