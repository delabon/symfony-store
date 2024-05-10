<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use OutOfBoundsException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository,
        private CartRepository $cartRepository,
        private Security $security
    )
    {
    }

    /**
     * @throws Exception
     */
    public function add(Product $product): void
    {
        if (!$product->getId()) {
            throw new InvalidArgumentException('Product must have an ID');
        }

        if (!$this->productRepository->find($product->getId())) {
            throw new OutOfBoundsException('Product not found');
        }

        $user = $this->security->getUser();

        if ($user instanceof User) {
            $this->addToDb($product, $user);
        } else {
            $this->addToSession($product);
        }
    }

    private function addToDb(Product $product, User $user): void
    {
        $cart = $user->getCart();

        if (!$cart instanceof Cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $cart->setItems([]);
            $cart->setCreatedAt(new DateTimeImmutable());
        }

        $cart->setUpdatedAt(new DateTimeImmutable());
        $items = $cart->getItems();

        if (!array_key_exists($product->getId(), $items)) {
            $items[$product->getId()] = 0;
        }

        $items[$product->getId()]++;
        $cart->setItems($items);
        $this->cartRepository->save($cart);
    }

    private function addToSession(Product $product): void
    {
        $cart = $this->requestStack->getSession()->get('cart', []);

        if (!array_key_exists($product->getId(), $cart)) {
            $cart[$product->getId()] = 0;
        }

        $cart[$product->getId()]++;
        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function get(): array
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            return $this->getFromDb($user);
        }

        return $this->getFromSession();
    }

    private function getFromDb(User $user): array
    {
        $cart = $user->getCart();

        if (!$cart instanceof Cart) {
            return [
                'items' => [],
                'total' => 0,
            ];
        }

        return $this->prepareItemsAndTotal($cart->getItems());
    }

    private function getFromSession(): array
    {
        return $this->prepareItemsAndTotal($this->requestStack->getSession()->get('cart', []));
    }

    private function prepareItemsAndTotal(array $cartItems): array
    {
        $products = $this->productRepository->findBy(['id' => array_keys($cartItems)]);
        $items = [];
        $total = 0;

        foreach ($products as $product) {
            $items[] = [
                'product' => $product,
                'quantity' => $cartItems[$product->getId()],
            ];
            $total += $cartItems[$product->getId()] * $product->getSalePrice();
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public function remove(Product $product): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $this->removeFromDb($product, $user);
        } else {
            $this->removeFromSession($product);
        }
    }

    private function removeFromDb(Product $product, User $user): void
    {
        $cart = $user->getCart();

        if (!$cart instanceof Cart) {
            throw new OutOfBoundsException('Cart not found');
        }

        $items = $cart->getItems();

        if (!array_key_exists($product->getId(), $items)) {
            throw new OutOfBoundsException('Product not in cart');
        }

        unset($items[$product->getId()]);

        $cart->setItems($items);
        $cart->setUpdatedAt(new DateTimeImmutable());
        $this->cartRepository->save($cart);
    }

    public function removeFromSession(Product $product): void
    {
        $cart = $this->requestStack->getSession()->get('cart', []);

        if (!array_key_exists($product->getId(), $cart)) {
            throw new OutOfBoundsException('Product not in cart');
        }

        unset($cart[$product->getId()]);
        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function quantity(Product $product, int $quantity): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $this->updateDbQuantity($product, $quantity, $user);
        } else {
            $this->UpdateSessionQuantity($product, $quantity);
        }
    }

    public function updateDbQuantity(Product $product, int $quantity, User $user): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1');
        }

        $cart = $user->getCart();

        if (!$cart instanceof Cart) {
            throw new OutOfBoundsException('Cart not found');
        }

        $items = $cart->getItems();

        if (!array_key_exists($product->getId(), $items)) {
            throw new OutOfBoundsException('Product not in cart');
        }

        $items[$product->getId()] = $quantity;
        $cart->setItems($items);
        $cart->setUpdatedAt(new DateTimeImmutable());
        $this->cartRepository->save($cart);
    }

    private function UpdateSessionQuantity(Product $product, int $quantity)
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1');
        }

        $cart = $this->requestStack->getSession()->get('cart', []);

        if (!array_key_exists($product->getId(), $cart)) {
            throw new OutOfBoundsException('Product not in cart');
        }

        $cart[$product->getId()] = $quantity;
        $this->requestStack->getSession()->set('cart', $cart);
    }
}