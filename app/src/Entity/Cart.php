<?php

namespace App\Entity;

use App\Repository\CartRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use OutOfBoundsException;

#[ORM\Entity(repositoryClass: CartRepository::class)]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'cart', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private array $items = [];

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): static
    {
        $this->items = $items;

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

    public function addItem(int $itemId): self
    {
        if (!array_key_exists($itemId, $this->items)) {
            $this->items[$itemId] = 0;
        }

        $this->items[$itemId]++;

        return $this;
    }

    public function removeItem(int $itemId): self
    {
        if (!array_key_exists($itemId, $this->items)) {
            throw new OutOfBoundsException('Item not found in cart');
        }

        unset($this->items[$itemId]);

        return $this;
    }

    public function updateItemQuantity(int $itemId, int $quantity): self
    {
        if (!array_key_exists($itemId, $this->items)) {
            throw new OutOfBoundsException('Item not found in cart');
        }

        $this->items[$itemId] = $quantity;

        return $this;
    }
}
