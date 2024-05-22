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

    #[ORM\Column(length: 255)]
    private string $hash = '';

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
        if (!in_array($itemId, $this->items)) {
            $this->items[] = $itemId;
        }

        return $this;
    }

    public function removeItem(int $itemId): self
    {
        if (!in_array($itemId, $this->items)) {
            throw new OutOfBoundsException('Item not found in cart');
        }

        $this->items = array_filter($this->items, function ($tempId) use ($itemId) {
            return $itemId !== $tempId;
        });

        return $this;
    }

    public function mergeItems(array $newItems): self
    {
        foreach ($newItems as $itemId) {
            if (!in_array($itemId, $this->items)) {
                $this->items[] = $itemId;
            }
        }

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): static
    {
        $this->hash = $hash;

        return $this;
    }

    public function generateHash(): string
    {
        $this->hash = hash('sha512', serialize($this->items) . uniqid());

        return $this->hash;
    }
}
